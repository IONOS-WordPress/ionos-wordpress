#!/usr/bin/env bash

#
# script is not intended to be executed directly. use 'pnpm exec ...' instead or call it as package script.
#
# this script is used to execute the tests
#
# run 'pnpm run test --help' for help
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/_bootstrap.sh"
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
      ionos.wordpress.print_help "$0"
      ;;
    --use)
      ionos.wordpress.parse_use_flag "$2"
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
ionos.wordpress.default_use_to_all

# in CI, keep the downloaded browsers inside the workspace instead of the dev container's
# ~/.cache/ms-playwright. the dev container is thrown away after every step, so the default
# location means re-downloading ~300MB (chromium + headless shell + ffmpeg) on every single
# run. the workspace is bind-mounted by devcontainers/ci, so a plain host-side actions/cache
# step can restore/save it - the same trick .github/shared/actions/pnpm-store-cache uses for
# .pnpm-store. left untouched outside CI so local dev keeps sharing the machine-wide cache.
if [[ "${CI:-}" == 'true' ]]; then
  export PLAYWRIGHT_BROWSERS_PATH="$(pwd)/.playwright-browsers"
fi

# ensure the playwright cache is generated in the same environment (devcontainer or local) as the tests are executed
# (this is necessary because the cache is not portable between environments)
if [[ "${USE[@]}" =~ e2e|react|all ]]; then
  # 'chrome-linux*' because playwright names the directory chrome-linux64 on x64 - the
  # old 'chrome-linux' pattern never matched, so this always reported 0 installations
  ionos.wordpress.log_info "found playwight installations : $(find "${PLAYWRIGHT_BROWSERS_PATH:-$HOME/.cache/ms-playwright}" -path "*/chrome-linux*/chrome" 2>/dev/null | wc -l)"

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
  # MARK: run a throwaway wordpress-alpine container, always destroyed afterwards. shared
  # by both PHPUnit and Playwright below (own name/mnt dir so it never collides with the
  # persistent dev stack from scripts/start.sh - see scripts/includes/_docker-mounts.sh
  # for the shared mount-discovery logic)
  readonly TEST_CONTAINER_NAME='ionos-wordpress-test'
  readonly TEST_STACK_DIR="${MNT_HOME}/test"
  readonly CORE_DIR="$(ionos.wordpress.core_dir "$WORDPRESS_VERSION")"
  # WordPress/WordPress (the release-build mirror used for WORDPRESS_VERSION) has no
  # tests/ directory at all - the test suite (WP_UnitTestCase and friends) only lives
  # in WordPress/wordpress-develop, always on `trunk` regardless of the core version
  # being tested.
  readonly TESTS_DIR="${MNT_HOME}/wordpress-tests/trunk"

  # PHP_VERSION_OVERRIDE runs the test stack against a non-default PHP version
  # published in packages/docker/wordpress-alpine/image-matrix.json (currently just
  # 8.4, the project's stated version - see AGENTS.md) - add a variant there to test
  # against it. It prefers the prebuilt image from the registry to avoid a local
  # build on every PR update, falling back to building it locally if the registry
  # doesn't have it (not published yet, or offline dev use). Both the accepted
  # versions and the Alpine branch each is paired with come from that same file,
  # the one .github/workflows/build-wordpress-alpine-image.yaml builds its matrix
  # from - so anything accepted here is something that actually gets published.
  # Source is written against PHP 8.4 syntax (see AGENTS.md), so this works in
  # source mode as-is - no TEST_PRODUCTION=true requirement.
  if [[ -n "${PHP_VERSION_OVERRIDE:-}" ]]; then
    readonly WORDPRESS_ALPINE_IMAGE_MATRIX='packages/docker/wordpress-alpine/image-matrix.json'
    readonly WORDPRESS_ALPINE_ALPINE_VERSION="$(
      jq -r --arg php "$PHP_VERSION_OVERRIDE" '.[] | select(.php == $php) | .alpine' "$WORDPRESS_ALPINE_IMAGE_MATRIX"
    )"
    if [[ -z "$WORDPRESS_ALPINE_ALPINE_VERSION" ]]; then
      ionos.wordpress.log_error \
        "PHP_VERSION_OVERRIDE=$PHP_VERSION_OVERRIDE is not one of the published wordpress-alpine image variants ($(jq -r '[.[].php] | join(", ")' "$WORDPRESS_ALPINE_IMAGE_MATRIX"))"
      exit 1
    fi

    # same tag the publish workflow assigns - resolved through the one script that owns
    # the repository-wide '<image>:<tag>' scheme, so the two can never drift apart
    readonly IMAGE_TAG="$(.github/shared/scripts/_docker-subproject-image-tag.sh packages/docker/wordpress-alpine)"
    readonly WORDPRESS_ALPINE_IMAGE="${IMAGE_REGISTRY}/${IMAGE_REPOSITORY}:${IMAGE_TAG}-php${PHP_VERSION_OVERRIDE}"

    if [[ -n "${IMAGE_REGISTRY_USERNAME:-}" ]] && [[ -n "${IMAGE_REGISTRY_PASSWORD:-}" ]]; then
      echo "$IMAGE_REGISTRY_PASSWORD" | docker login "$IMAGE_REGISTRY" --username "$IMAGE_REGISTRY_USERNAME" --password-stdin
    fi

    ionos.wordpress.log_info "PHP_VERSION_OVERRIDE=$PHP_VERSION_OVERRIDE set - pulling prebuilt test image $WORDPRESS_ALPINE_IMAGE ..."
    # retry: a push that touches both packages/docker/wordpress-alpine/** and CI can race
    # the separate build-wordpress-alpine-image.yaml publish job that tags this same commit.
    # backoff, not a flat wait: the common case is "not published yet" (not a transient
    # failure), which the local-build fallback below handles anyway - short early retries
    # reach that fallback fast, longer later ones still give a racing publish job a real
    # chance to land.
    PULL_RETRY_DELAYS=(5 10 20 30)
    PULLED=no
    for i in $(seq 1 5); do
      docker pull "$WORDPRESS_ALPINE_IMAGE" && { PULLED=yes; break; }
      [[ $i -eq 5 ]] && break
      DELAY="${PULL_RETRY_DELAYS[$((i - 1))]}"
      ionos.wordpress.log_warn "pull failed, image may still be publishing - retrying in ${DELAY}s ($i/5) ..."
      sleep "$DELAY"
    done

    if [[ "$PULLED" != 'yes' ]]; then
      ionos.wordpress.log_warn "could not pull $WORDPRESS_ALPINE_IMAGE from the registry - building it locally instead"
      docker build \
        --build-arg ARG_PHP_VERSION="$PHP_VERSION_OVERRIDE" \
        --build-arg ARG_ALPINE_VERSION="$WORDPRESS_ALPINE_ALPINE_VERSION" \
        --build-arg HOST_UID="$(id -u)" \
        --build-arg HOST_GID="$(id -g)" \
        -t "$WORDPRESS_ALPINE_IMAGE" \
        -f packages/docker/wordpress-alpine/Dockerfile \
        .
    fi
  else
    readonly WORDPRESS_ALPINE_IMAGE='ionos-wordpress/wordpress-alpine:latest'

    # 'pnpm test' is run standalone in places that never ran a build first (scripts/
    # pre-release.sh, a fresh clone) and this script itself never builds - without this
    # guard the throwaway container below dies with a bare "pull access denied for
    # ionos-wordpress/wordpress-alpine" from the docker daemon. building just that one workspace
    # package is a no-op whenever the image is already there.
    if ! docker image inspect "$WORDPRESS_ALPINE_IMAGE" &>/dev/null; then
      ionos.wordpress.log_info "$WORDPRESS_ALPINE_IMAGE not available locally - building it ..."
      pnpm run build --filter '@ionos-wordpress/wordpress-alpine'
    fi
  fi

  if [[ ! -d "$TESTS_DIR/tests/phpunit/includes" ]]; then
    ionos.wordpress.log_info "cloning WordPress/wordpress-develop#trunk test suite into ${TESTS_DIR} ..."
    rm -rf "$TESTS_DIR"
    # sparse/partial clone: a full checkout of wordpress-develop is ~229MB, but the only
    # thing ever consumed is tests/phpunit (~52MB), which is all that gets mounted into the
    # test container as /wordpress-phpunit below. blob:none + sparse-checkout fetches just
    # that subtree, which is markedly quicker than a full clone on every CI run.
    git clone --quiet --depth 1 --branch trunk --filter=blob:none --sparse \
      https://github.com/WordPress/wordpress-develop.git "$TESTS_DIR"
    git -C "$TESTS_DIR" sparse-checkout set --no-cone tests/phpunit
  fi

  # starts the throwaway test container (detached). CORE_DIR is shared and read-only in
  # practice, exactly as the dev stack already shares it.
  function ionos.wordpress.start_test_container() {
    local name="$TEST_CONTAINER_NAME"
    local port="$TEST_HTTP_PORT"

    VOLUME_ARGS=()
    ionos.wordpress.build_wp_volume_args "$TEST_STACK_DIR" "$CORE_DIR"
    VOLUME_ARGS+=(
      --volume "$(pwd)/${TESTS_DIR}/tests/phpunit:/wordpress-phpunit"
      --volume "$(pwd)/phpunit:/htdocs/phpunit"
      --volume "$(pwd)/phpunit/wp-tests-config.php:/wordpress-phpunit/wp-tests-config.php:ro"
    )

    # the same AFTER_START customization the dev stack gets (see scripts/start.sh): brand
    # options, static front page, plugin-activation exclusions. Without it the test container
    # serves a plainer site than the one developers actually look at, and every e2e spec has to
    # reproduce the difference in its own beforeAll.
    #
    # deliberately only mounted, not passed as --env AFTER_START: that would make
    # docker-entrypoint.sh run it on boot, and phpunit shares the live site's tables (see
    # phpunit/wp-tests-config.php's wp_ table prefix), so the state it leaves behind reaches the
    # PHPUnit run. IONOS_CUSTOM_DELETED_PLUGINS_OPTION is enough to stop stretch-extra loading
    # its provisioned ionos-essentials copy, which is what defines the constants the wpscan tests
    # need. The e2e step below runs the script itself, after phpunit is done.
    if [[ -n "${AFTER_START:-}" ]]; then
      VOLUME_ARGS+=(--volume "$(realpath "$AFTER_START"):/after-start.sh:ro")
    fi

    # guard against a stale leftover container from a previous crashed run
    docker rm -f "$name" &>/dev/null || true

    docker run \
      --detach \
      --tty \
      --interactive \
      --name "$name" \
      --hostname "$name" \
      --publish "${port}:80" \
      --add-host "host.docker.internal:host-gateway" \
      --env WORDPRESS_VERSION="$WORDPRESS_VERSION" \
      --env WP_PASSWORD="$WP_PASSWORD" \
      --env HTTP_PORT="$port" \
      --env WORDPRESS_DB_HOST=localhost \
      --env WORDPRESS_DB_NAME=wordpress \
      --env WORDPRESS_DB_USER=wordpress \
      --env WORDPRESS_DB_PASSWORD=password \
      --env WORDPRESS_CONFIG_EXTRA="define('ABSPATH','/htdocs/');" \
      --env WP_TESTS_DIR=/wordpress-phpunit \
      --env HOST_UID="$(id -u)" \
      --env HOST_GID="$(id -g)" \
      "${VOLUME_ARGS[@]}" \
      "$WORDPRESS_ALPINE_IMAGE" >/dev/null
  }

  # blocks until the test container is usable, then makes it safe to test against.
  #
  # readiness means "docker-entrypoint.sh has finished", not "WordPress is installed".
  # The weaker check this used to make ('wp core is-installed') goes true the moment the
  # entrypoint's own `wp core install` returns, while it still has a rewrite flush, sshd,
  # httpd and AFTER_START ahead of it. Starting phpunit inside that window is actively
  # destructive: phpunit's bootstrap drops and recreates the wp_ tables it shares with the
  # live site (see phpunit/wp-tests-config.php's table prefix), the entrypoint's next
  # wp-cli call then dies with "The site you have requested is not installed", and because
  # it runs under `set -e` that takes the container down - SIGKILLing the phpunit exec,
  # which surfaced as an intermittent exit 137 right after "Installing...".
  #
  # It only showed up when a test file was passed on the command line, because that skips
  # the target-php-version syntax checks below, whose runtime had been masking the race.
  function ionos.wordpress.await_test_container() {
    local name="$TEST_CONTAINER_NAME"

    ionos.wordpress.log_info "waiting for test container $name to come up ..."
    # generous budget: a cold run (fresh core download/install, no shared cache
    # yet) is slower in CI's nested docker-in-docker devcontainer than locally
    for _ in $(seq 1 180); do
      if docker exec "$name" test -f /run/entrypoint-complete 2>/dev/null; then
        # WordPress' background auto-updater takes the whole site down behind core's
        # .maintenance file while it runs (WP_Automatic_Updater -> WP_Upgrader::
        # maintenance_mode), so any request that races it comes back 503 "Briefly
        # unavailable for scheduled maintenance". that is what made mcp.spec.js flaky:
        # it is the one spec asserting the console error list is empty, so it is the one
        # that notices. nothing in the suite wants core/plugin/theme auto-updates.
        #
        # set here rather than baked into the image so it stays a property of the test
        # container alone - the dev stack deliberately keeps auto-updates. the window is not
        # raced: readiness above is established over docker exec, never over HTTP, so the
        # site has served no request yet - no request means no wp-cron, which means the
        # updater cannot have started.
        docker exec --user php "$name" \
          wp --quiet config set AUTOMATIC_UPDATER_DISABLED true --raw --type=constant --path=/htdocs
        return 0
      fi
      sleep 1
    done

    ionos.wordpress.log_error "test container $name did not become ready within the timeout"
    # an image built before the readiness marker existed can never satisfy the check above,
    # and would otherwise just time out with nothing pointing at the cause. Only `pnpm start`
    # rebuilds the image implicitly (via scripts/build.sh); `pnpm test:*` reuses whatever is
    # already tagged locally.
    if ! docker exec "$name" grep -q entrypoint-complete /docker-entrypoint.sh 2>/dev/null; then
      # single argument on purpose: log_error's second parameter is a stacktrace index and it
      # aborts if given anything non-numeric
      ionos.wordpress.log_error "$WORDPRESS_ALPINE_IMAGE predates the readiness marker - rebuild it via 'pnpm build'"
    fi
    docker logs "$name" || true
    exit 1
  }

  function ionos.wordpress.cleanup_test_container {
    docker rm -f "$TEST_CONTAINER_NAME" &>/dev/null || true
    rm -rf "$TEST_STACK_DIR"
  }
  trap ionos.wordpress.cleanup_test_container EXIT

  ionos.wordpress.start_test_container
  ionos.wordpress.await_test_container
fi

if [[ "${USE[@]}" =~ all|php ]]; then
  # test distributable plugin code is correctly transformed by rector
  # by testing its syntax againts the transpiler target language
  if [[ ${#POSITIONAL_ARGS[@]} -eq 0 ]]; then
    # for each wp-plugin and wp-mu-plugin in the packages directory
    for transpiled_plugin_dir in $(find packages \( -path '*/wp-plugin/*/dist/*-?.?.?-php?.?' -o -path '*/wp-mu-plugin/*/dist/*-?.?.?-php?.?' \) -type d -name '*-?.?.?-php?.?'); do
      # get the target php version from the directory name
      TARGET_PHP_VERSION=$(echo "${transpiled_plugin_dir#*php}" | grep -oE '^[0-9.]+')

      ionos.wordpress.log_header "checking compatibility for target php version $TARGET_PHP_VERSION in plugin $transpiled_plugin_dir"
      # check if the transpiled plugin code (except for phpunit test files ) is valid for the desired php version
      SYNTAX_CHECK_OUTPUT=$(cat <<EOL | docker run -i --rm -v "$PWD":/usr/src/myapp -w /usr/src/myapp php:${TARGET_PHP_VERSION}-cli /bin/bash -
find "$transpiled_plugin_dir" -name "*.php" -not -name "*Test.php" -not -path "*/stretch-extra/stretch-extra/*" -print0 | xargs -0L1 php -l
exit $?
EOL
      )
      SYNTAX_CHECK_STATUS=$?

      if [[ $SYNTAX_CHECK_STATUS -ne 0 ]] || [[ -z "$SYNTAX_CHECK_OUTPUT" ]] || echo "$SYNTAX_CHECK_OUTPUT" | grep -qv '^No syntax errors'; then
        echo "$SYNTAX_CHECK_OUTPUT"
        exit 1
      fi
    done
  else
    ionos.wordpress.log_info "skipped target php version syntax checks since individual PHPUnit test files are provided as commandline arguments"
  fi

  # provide part specific options and all positional arguments that are php files
  # (files will be converted to '--filter *TestCase' arguments to match PHPUnit expectations).
  # run via `sh -c` (not separate argv entries) so USE_OPTIONS[php] can itself contain
  # multiple space-separated phpunit options.
  docker exec --user php "$TEST_CONTAINER_NAME" sh -c \
    "phpunit -c /htdocs/phpunit/phpunit.xml ${USE_OPTIONS[php]} \
    $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.php ]] && printf -- "--filter '%s' " $(basename $file .php); done)"
fi

if [[ "${USE[@]}" =~ all|e2e ]]; then
  # apply the dev stack's AFTER_START customization now rather than repeating parts of it here
  # (it already resets the admin password and the compromised-credentials meta, which is what
  # this step used to do by hand). It has to happen after phpunit, not on container boot: phpunit
  # reinstalls WordPress into the same tables, dropping everything AFTER_START set up. It runs as
  # root and drops to `php` via doas itself, matching how docker-entrypoint.sh invokes it.
  if [[ -n "${AFTER_START:-}" ]]; then
    ionos.wordpress.log_info "applying AFTER_START ($AFTER_START) to the test container ..."
    docker exec "$TEST_CONTAINER_NAME" /after-start.sh >/dev/null
  fi

  # run e2e tests against the ephemeral test container's published port. provide part
  # specific options and all positional arguments that are php files.
  export WP_BASE_URL="http://localhost:${TEST_HTTP_PORT}"
  export WP_ARTIFACTS_PATH="$(pwd)/playwright/e2e/.artifacts"
  export STORAGE_STATE_PATH="$(pwd)/playwright/e2e/.storage-states/admin.json"
  pnpm exec playwright test --pass-with-no-tests -c ./playwright.config.js \
    ${USE_OPTIONS[e2e]:---quiet} \
    $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.js ]] && printf "$file "; done)
fi

exit

###help-message
Syntax: 'pnpm run test [options] [additional-args]'

Executes tests.

If PHPUnit or e2e tests will be run, a throwaway wordpress-alpine test container is started
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

