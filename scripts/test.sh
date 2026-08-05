#!/usr/bin/env bash

#
# script is not intended to be executed directly. use 'pnpm exec ...' instead or call it as package script.
#
# this script is used to execute the tests
#
# run 'pnpm run test --help' for help
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"
source "$(realpath $0 | xargs dirname)/includes/_docker-mounts.sh"

# test file arguments
POSITIONAL_ARGS=()

# array of flags indicating which kind of tests (react,php,...) to execute
USE=()

# options per kind of test
declare -A USE_OPTIONS

while [[ $# -gt 0 ]]; do
  case $1 in
    --help)
      # print everything in this script file after the '###help-message' marker
      printf "$(sed -e '1,/^###help-message/d' "$0")\n"
      exit
      ;;
    --use)
      # convert value to lowercase and append value to USE array
      USE+=("${2,,}")
      shift 2
      ;;
    --react-opts)
      USE_OPTIONS+=("react" "${2}")
      shift 2
      ;;
    --php-opts)
      USE_OPTIONS+=("php" "${2}")
      shift 2
      ;;
    --e2e-opts)
      USE_OPTIONS+=("e2e" "${2}")
      shift 2
      ;;
    -*|--*)
      echo "Unknown option $1"
      exit 1
      ;;
    *)
      POSITIONAL_ARGS+=("$1")
      shift # past argument
      ;;
  esac
done

# invoke all tests by default
[[ ${#USE[@]} -eq 0 ]] && USE=("all")

# ensure the playwright cache is generated in the same environment (devcontainer or local) as the tests are executed
# (this is necessary because the cache is not portable between environments)
if [[ "${USE[@]}" =~ e2e|react|all ]]; then
  ionos.wordpress.log_info "found playwight installations : $(find ~/.cache/ms-playwright -path "*/chrome-linux/chrome" 2>/dev/null | wc -l)"

  PLAYWRIGHT_DIR=$(realpath ./playwright)
  if [[ -f "$PLAYWRIGHT_DIR/.cache/metainfo.json" ]] && ! grep "$PLAYWRIGHT_DIR" ./playwright/.cache/metainfo.json > /dev/null; then
    # ./playwright/.cache/metainfo.json contains not the absolute path to the cache directory of the current environment
    rm -rf "$PLAYWRIGHT_DIR/.cache"
  fi

  # execute playwright browser installation if not already done
  pnpm exec playwright install chromium
fi


if [[ "${USE[@]}" =~ all|react ]]; then
  (
    # execute playwright tests. provide part specific options and all positional arguments that are jsx files
    pnpm exec playwright test --pass-with-no-tests -c ./playwright-ct.config.js \
      "${USE_OPTIONS[react]:---quiet}" \
      $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.jsx ]] && printf "$file "; done)
  )
fi

if [[ "${USE[@]}" =~ all|php|e2e ]]; then
  # MARK: run a throwaway wp-alpine container, always destroyed afterwards, shared by
  # both PHPUnit and Playwright below (own name/mnt dir so it never collides with the
  # persistent dev stack from scripts/start.sh - see scripts/includes/_docker-mounts.sh
  # for the shared mount-discovery logic)
  readonly TEST_CONTAINER_NAME='ionos-wordpress-test'
  readonly VERSION_DIR="$(ionos.wordpress.wordpress_version_dir "$WORDPRESS_VERSION")"
  readonly CORE_DIR="${MNT_HOME}/wordpress-core/${VERSION_DIR}"
  readonly TEST_STACK_DIR="${MNT_HOME}/test"
  # WordPress/WordPress (the release-build mirror used for WORDPRESS_VERSION) has no
  # tests/ directory at all - the test suite (WP_UnitTestCase and friends) only lives
  # in WordPress/wordpress-develop, always on `trunk` regardless of the core version
  # being tested (confirmed against a real wp-env cache's tests-WordPress-PHPUnit/.git
  # remote - wp-env clones this same fixed repo/branch, decoupled from WP_ENV_CORE).
  readonly TESTS_DIR="${MNT_HOME}/wordpress-tests/trunk"

  # PHP_VERSION_OVERRIDE runs the test stack against a prebuilt legacy-PHP image
  # from the registry instead of the local PHP 8.4 build (see .github/workflows/
  # build-wp-alpine-image.yaml's published matrix) - no local image build, since
  # this path runs on every PR update and locally, not just occasionally.
  if [[ -n "${PHP_VERSION_OVERRIDE:-}" ]]; then
    if [[ "$PHP_VERSION_OVERRIDE" != '7.4' ]]; then
      ionos.wordpress.log_error "PHP_VERSION_OVERRIDE=$PHP_VERSION_OVERRIDE is not part of the prebuilt wp-alpine image matrix (7.4)"
      exit 1
    fi

    readonly IMAGE_CONTENT_HASH="$(git rev-parse HEAD:packages/docker/wp-alpine)"
    readonly WP_ALPINE_IMAGE="${IMAGE_REGISTRY}/${IMAGE_REPOSITORY}:${IMAGE_CONTENT_HASH}-php${PHP_VERSION_OVERRIDE}"

    if [[ -n "${IMAGE_REGISTRY_USERNAME:-}" ]] && [[ -n "${IMAGE_REGISTRY_PASSWORD:-}" ]]; then
      echo "$IMAGE_REGISTRY_PASSWORD" | docker login "$IMAGE_REGISTRY" --username "$IMAGE_REGISTRY_USERNAME" --password-stdin
    fi

    ionos.wordpress.log_info "PHP_VERSION_OVERRIDE=$PHP_VERSION_OVERRIDE set - pulling prebuilt test image $WP_ALPINE_IMAGE (no local build) ..."
    docker pull "$WP_ALPINE_IMAGE"
  else
    readonly WP_ALPINE_IMAGE='ionos-wordpress/wp-alpine:latest'
  fi

  if [[ ! -d "$TESTS_DIR/tests/phpunit/includes" ]]; then
    ionos.wordpress.log_info "cloning WordPress/wordpress-develop#trunk test suite into ${TESTS_DIR} ..."
    rm -rf "$TESTS_DIR"
    git clone --quiet --depth 1 --branch trunk https://github.com/WordPress/wordpress-develop.git "$TESTS_DIR"
  fi

  VOLUME_ARGS=()
  ionos.wordpress.build_wp_volume_args "$TEST_STACK_DIR" "$CORE_DIR"
  VOLUME_ARGS+=(
    --volume "$(pwd)/${TESTS_DIR}/tests/phpunit:/wordpress-phpunit"
    --volume "$(pwd)/phpunit:/htdocs/phpunit"
    --volume "$(pwd)/phpunit/wp-tests-config.php:/wordpress-phpunit/wp-tests-config.php:ro"
  )

  # guard against a stale leftover container from a previous crashed run
  docker rm -f "$TEST_CONTAINER_NAME" &>/dev/null || true

  function ionos.wordpress.cleanup_test_container {
    docker rm -f "$TEST_CONTAINER_NAME" &>/dev/null || true
    rm -rf "$TEST_STACK_DIR"
  }
  trap ionos.wordpress.cleanup_test_container EXIT

  docker run \
    --detach \
    --tty \
    --interactive \
    --name "$TEST_CONTAINER_NAME" \
    --hostname "$TEST_CONTAINER_NAME" \
    --publish "${TEST_HTTP_PORT}:80" \
    --env WORDPRESS_VERSION="$WORDPRESS_VERSION" \
    --env WP_PASSWORD="$WP_PASSWORD" \
    --env HTTP_PORT="$TEST_HTTP_PORT" \
    --env WORDPRESS_DB_HOST=localhost \
    --env WORDPRESS_DB_NAME=wordpress \
    --env WORDPRESS_DB_USER=wordpress \
    --env WORDPRESS_DB_PASSWORD=password \
    --env WORDPRESS_CONFIG_EXTRA="define('ABSPATH','/htdocs/');" \
    --env WP_TESTS_DIR=/wordpress-phpunit \
    "${VOLUME_ARGS[@]}" \
    "$WP_ALPINE_IMAGE" >/dev/null

  # readiness: phpunit talks to the DB directly, never over HTTP, so "wp core
  # is-installed" (WP core downloaded + wp-config.php + database ready) is the right
  # check here rather than an HTTP request.
  ionos.wordpress.log_info "waiting for the test container to come up ..."
  READY=
  for i in $(seq 1 60); do
    if docker exec --user php "$TEST_CONTAINER_NAME" wp core is-installed --path=/htdocs 2>/dev/null; then
      READY=1
      break
    fi
    sleep 1
  done
  if [[ -z "$READY" ]]; then
    ionos.wordpress.log_error "test container did not become ready within the timeout"
    exit 1
  fi
fi

if [[ "${USE[@]}" =~ all|php ]]; then
  # test distributable plugin code is correctly transformed by rector
  # by testing its syntax againts the transpiler target language
  if [[ ${#POSITIONAL_ARGS[@]} -eq 0 ]]; then
    # for each wp-plugin and wp-mu-plugin in the packages directory
    for transpiled_plugin_dir in $(find packages -path '*/wp-plugin/*/dist/*-?.?.?-php?.?' -o -path '*/wp-mu-plugin/*/dist/*-?.?.?-php?.?' -type d -name '*-?.?.?-php?.?'); do
      # get the target php version from the directory name
      TARGET_PHP_VERSION=$(echo "${transpiled_plugin_dir#*php}" | grep -oE '^[0-9.]+')

      ionos.wordpress.log_header "checking compatibility for target php version $TARGET_PHP_VERSION in plugin $transpiled_plugin_dir"
      # check if the transpiled plugin code (except for phpunit test files ) is valid for the desired php version
      ! cat <<EOL | docker run -i --rm -v "$PWD":/usr/src/myapp -w /usr/src/myapp php:${TARGET_PHP_VERSION}-cli /bin/bash - | grep -v '^No syntax errors'
find "$transpiled_plugin_dir" -name "*.php" -not -name "*Test.php" -not -path "*/stretch-extra/stretch-extra/*" -print0 | xargs -0L1 php -l
exit $?
EOL

      if [[ $? -ne 0 ]]; then
        exit 1
      fi
    done
  else
    ionos.wordpress.log_info "skipped target php version syntax checks since individual PHPUnit test files are provided as commandline arguments"
  fi

  # provide part specific options and all positional arguments that are php files
  # (files will be converted to '--filter *TestCase' arguments to match PHPUnit expectations).
  # run via `sh -c` (not separate argv entries) so USE_OPTIONS[php] can itself contain
  # multiple space-separated phpunit options, matching the previous wp-env behavior.
  docker exec --user php "$TEST_CONTAINER_NAME" sh -c \
    "phpunit -c /htdocs/phpunit/phpunit.xml ${USE_OPTIONS[php]} \
    $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.php ]] && printf -- "--filter '%s' " $(basename $file .php); done)"
fi

if [[ "${USE[@]}" =~ all|e2e ]]; then
  # next 2 steps are required since a preceding phpunit run resets the database to a
  # state that is not suitable for e2e tests
  # set the default admin password to the password defined in .env file
  docker exec --user php "$TEST_CONTAINER_NAME" wp --quiet user update admin --user_pass="${WP_PASSWORD}" --path=/htdocs
  # reset the user meta for compromised credentials check
  docker exec --user php "$TEST_CONTAINER_NAME" wp --quiet user meta delete admin ionos_compromised_credentials_check_leak_detected_v2 --path=/htdocs &>/dev/null || true

  # run e2e tests against the ephemeral test container's published port. provide part
  # specific options and all positional arguments that are php files
  (
    export WP_BASE_URL="http://localhost:${TEST_HTTP_PORT}"
    pnpm exec playwright test --pass-with-no-tests -c ./playwright.config.js \
      ${USE_OPTIONS[e2e]:---quiet} \
      $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.js ]] && printf "$file "; done)
  )
fi

exit

###help-message
Syntax: 'pnpm run test [options] [additional-args]'

Executes tests.

If PHPUnit or e2e tests will be run, a throwaway wp-alpine test container is started
and torn down again afterwards (pass or fail).

Options:

  --help    Show this help message and exit

  --use     Specify which tests to execute (default: all). Can be applied multiple times.

            Available options:

              - php       execute PHPUnit tests

                --php-opts '<options>'  Additional options to pass to PHPUnit

                Execute "pnpm run test:php --php-opts '--help'" to see all PHPUnit options

              - e2e       execute E2E tests

                --e2e-opts '<options>'  Additional options to pass to Playwright

                Execute "pnpm run test:e2e --e2e-opts '--help'" to see all Playwright options

              - react     execute Storybook/React tests

                --react-opts '<options>'  Additional options to pass to Playwright

                Execute "pnpm run test:react --react-opts '--help'" to see all Playwright options

  Usage:
    Execute only react tests :
      'pnpm run test:react' or
      'pnpm run test --use react'

    Execute only a single react test file :
      'pnpm run test:react MyButton.spec.jsx' (path can be left off) or
      'pnpm run test:react packages/wp-plugin/test-plugin/src/feature-1/blocks/block-1/components/tests/MyButton.spec.jsx' or

    Execute only a single react test file with playwright debugger :
      'pnpm run test:react --react-opts '--debug' MyButton.spec.jsx' (path can be left off for playwright) or
      'pnpm run test:react --react-opts '--debug' packages/wp-plugin/test-plugin/src/feature-1/blocks/block-1/components/tests/MyButton.spec.jsx' or

    Execute only PHPUnit tests:
      'pnpm run test:php' or
      'pnpm run test --use php'

    Execute all PHPUnit test case methods that contain 'test_login_admin' in their name:
      'pnpm run test:php --php-opts "--filter test_login_admin"' or
      'pnpm run test --use php --php-opts "--filter test_login_admin"'

    Execute all PHPUnit test classes contain 'LoginTest' in their name:
      'pnpm test:php --php-opts "--filter LoginTest"' or
      'pnpm run test --use php --php-opts "--filter LoginTest"' or
      'pnpm test -- --use php --php-opts "--filter LoginTest"'

    Execute all PHPUnit tests that are part of the 'test-plugin' group:
      'pnpm run test:php --php-opts '--group test-plugin' or
      'pnpm run test --use php --php-opts '--group test-plugin'

    Execute only e2e tests :
      'pnpm run test:e2e' or
      'pnpm run test --use e2e'

    Execute only a single e2e test file :
      'pnpm run test:e2e packages/wp-plugin/ionos-essentials/inc/dashboard/tests/e2e/deep-links-block.spec.js' or
      'pnpm run test:e2e deep-links-block.spec.js' (path can be left off for playwright)

    Execute e2e tests by tag (https://playwright.dev/docs/test-annotations#tag-tests) :
      # run all tests except tagged with @editor
      'pnpm run test:e2e --e2e-opts'

    Execute only a single e2e test file with playwright debugger :
      'pnpm run test:e2e --e2e-opts '--debug' packages/wp-plugin/ionos-essentials/inc/dashboard/tests/e2e/deep-links-block.spec.js' or
      'pnpm run test:e2e --e2e-opts '--debug' deep-links-block.spec.js' (path can be left off for playwright)

    Execute only PHPUnit and E2e tests:
      'pnpm run test --use e2e --use php'

see ./docs/5-test.md for more informations

