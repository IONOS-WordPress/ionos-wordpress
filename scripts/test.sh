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

# MARK: e2e sharding
# the e2e suite is written against ONE mutable WordPress: specs set up global state in
# beforeAll via wp-cli and actively contradict each other (welcome.spec.js deletes the
# user meta 'ionos_essentials_welcome' that tabs/maintenance/security-options set, and
# secondary-plugin-dir.spec.js deactivates ionos-essentials for its whole duration). so
# playwright's own 'workers' knob cannot be raised - instead we give each shard its OWN
# throwaway container and let playwright split the spec files across them with --shard.
#
# defaults to 1 (unchanged, single-container behaviour). CI sets E2E_SHARDS explicitly.
E2E_SHARDS="${E2E_SHARDS:-1}"
if [[ ! "$E2E_SHARDS" =~ ^[1-9][0-9]*$ ]]; then
  ionos.wordpress.log_error "E2E_SHARDS must be a positive integer, got '$E2E_SHARDS'"
  exit 1
fi
# a targeted run ('pnpm test:e2e foo.spec.js') is a single file - sharding it would leave
# every shard but one with nothing to do, and --shard would make which one lands the file
# unpredictable. same when e2e isn't being run at all.
if [[ ${#POSITIONAL_ARGS[@]} -gt 0 ]] || [[ ! "${USE[@]}" =~ all|e2e ]]; then
  E2E_SHARDS=1
fi

# the suffix for shard-scoped resource names: empty for shard 1 (keeping its historical
# unsuffixed name), "-<shard>" for every other shard.
#
# NOT the same rule as the e2e loop's SHARD_SUFFIX further below, which suffixes every
# shard - including 1 - whenever E2E_SHARDS > 1 (to keep concurrently running shards'
# artifacts/storage-state paths apart even for shard 1). that's a deliberate, different
# condition (total shard count vs. this shard's own number), not something to unify here.
#
# @param $1 shard number
#
function ionos.wordpress.shard_name_suffix() {
  [[ "$1" == '1' ]] && echo '' || echo "-$1"
}

# shard 1 keeps the historical name/port/mnt dir: PHPUnit runs against it, and it is the
# default playwright/exec-test-cli.js talks to when TEST_CONTAINER_NAME is unset.
function ionos.wordpress.test_container_name() {
  echo "ionos-wordpress-test$(ionos.wordpress.shard_name_suffix "$1")"
}
function ionos.wordpress.test_container_port() {
  echo "$((TEST_HTTP_PORT + $1 - 1))"
}
function ionos.wordpress.test_stack_dir() {
  echo "${MNT_HOME}/test$(ionos.wordpress.shard_name_suffix "$1")"
}

if [[ "${USE[@]}" =~ all|php|e2e ]]; then
  # MARK: run throwaway wordpress-alpine containers, always destroyed afterwards. shard 1 is
  # shared by both PHPUnit and Playwright below (own name/mnt dir so it never collides
  # with the persistent dev stack from scripts/start.sh - see
  # scripts/includes/_docker-mounts.sh for the shared mount-discovery logic)
  # not readonly: the e2e block below re-exports TEST_CONTAINER_NAME per shard (in a
  # subshell) so playwright/exec-test-cli.js targets that shard's container
  TEST_CONTAINER_NAME="$(ionos.wordpress.test_container_name 1)"
  readonly CORE_DIR="$(ionos.wordpress.core_dir "$WORDPRESS_VERSION")"
  # WordPress/WordPress (the release-build mirror used for WORDPRESS_VERSION) has no
  # tests/ directory at all - the test suite (WP_UnitTestCase and friends) only lives
  # in WordPress/wordpress-develop, always on `trunk` regardless of the core version
  # being tested.
  readonly TESTS_DIR="${MNT_HOME}/wordpress-tests/trunk"

  # PHP_VERSION_OVERRIDE runs the test stack against a non-default PHP version -
  # 8.3, the project's stated minimum (see AGENTS.md), is what this exists for. It
  # prefers the prebuilt image from the registry to avoid a local build on every PR
  # update, falling back to building it locally if the registry doesn't have it
  # (not published yet, or offline dev use). Both the accepted versions and the
  # Alpine branch each is paired with come from packages/docker/wordpress-alpine/
  # image-matrix.json, the same file .github/workflows/build-wordpress-alpine-image.yaml
  # builds its matrix from - so anything accepted here is something that actually
  # gets published. Source is written against PHP 8.3+ syntax (see AGENTS.md), so
  # this works in source mode as-is - no TEST_PRODUCTION=true requirement.
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
    # the separate build-wordpress-alpine-image.yaml publish job that tags this same commit
    PULLED=no
    for i in $(seq 1 5); do
      docker pull "$WORDPRESS_ALPINE_IMAGE" && { PULLED=yes; break; }
      [[ $i -eq 5 ]] && break
      ionos.wordpress.log_warn "pull failed, image may still be publishing - retrying in 30s ($i/5) ..."
      sleep 30
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

  # starts one shard's throwaway container (detached). every shard gets its own
  # container name, published port and wp-content overlay dir; CORE_DIR is shared and
  # read-only in practice, exactly as the dev and test stacks already share it.
  #
  # @param $1 shard number (1-based)
  function ionos.wordpress.start_test_container() {
    local shard="$1"
    local name port stack_dir
    name="$(ionos.wordpress.test_container_name "$shard")"
    port="$(ionos.wordpress.test_container_port "$shard")"
    stack_dir="$(ionos.wordpress.test_stack_dir "$shard")"

    VOLUME_ARGS=()
    ionos.wordpress.build_wp_volume_args "$stack_dir" "$CORE_DIR"
    VOLUME_ARGS+=(
      --volume "$(pwd)/${TESTS_DIR}/tests/phpunit:/wordpress-phpunit"
      --volume "$(pwd)/phpunit:/htdocs/phpunit"
      --volume "$(pwd)/phpunit/wp-tests-config.php:/wordpress-phpunit/wp-tests-config.php:ro"
    )

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

  # blocks until a shard's container is usable, then makes it safe to test against.
  #
  # readiness: phpunit talks to the DB directly, never over HTTP, so "wp core
  # is-installed" (WP core downloaded + wp-config.php + database ready) is the right
  # check here rather than an HTTP request.
  #
  # @param $1 shard number (1-based)
  function ionos.wordpress.await_test_container() {
    local shard="$1"
    local name
    name="$(ionos.wordpress.test_container_name "$shard")"

    ionos.wordpress.log_info "waiting for test container $name to come up ..."
    # generous budget: a cold run (fresh core download/install, no shared cache
    # yet) is slower in CI's nested docker-in-docker devcontainer than locally
    for _ in $(seq 1 180); do
      if docker exec --user php "$name" wp core is-installed --path=/htdocs 2>/dev/null; then
        # WordPress' background auto-updater takes the whole site down behind core's
        # .maintenance file while it runs (WP_Automatic_Updater -> WP_Upgrader::
        # maintenance_mode), so any request that races it comes back 503 "Briefly
        # unavailable for scheduled maintenance". that is what made mcp.spec.js flaky:
        # it is the one spec asserting the console error list is empty, so it is the one
        # that notices. nothing in the suite wants core/plugin/theme auto-updates.
        #
        # setting it here rather than in the image leaves packages/docker/wordpress-alpine
        # uncommitted-to and therefore its image tag (and the prebuilt-image cache behind
        # it) untouched. the window is
        # not raced: readiness above is checked over wp-cli, so the site has served no
        # HTTP request yet - no request means no wp-cron, which means the updater cannot
        # have started.
        docker exec --user php "$name" \
          wp --quiet config set AUTOMATIC_UPDATER_DISABLED true --raw --type=constant --path=/htdocs
        return 0
      fi
      sleep 1
    done

    ionos.wordpress.log_error "test container $name did not become ready within the timeout"
    docker logs "$name" || true
    exit 1
  }

  function ionos.wordpress.cleanup_test_container {
    local shard
    for shard in $(seq 1 "$E2E_SHARDS"); do
      docker rm -f "$(ionos.wordpress.test_container_name "$shard")" &>/dev/null || true
      rm -rf "$(ionos.wordpress.test_stack_dir "$shard")"
    done
  }
  trap ionos.wordpress.cleanup_test_container EXIT

  # shard 1 first, and fully: on a cold CORE_DIR it is the container that downloads and
  # extracts WordPress core into the shared cache. starting the rest concurrently with
  # that would have them race over the same half-written core tree.
  ionos.wordpress.start_test_container 1
  ionos.wordpress.await_test_container 1

  # the remaining shards find a warm CORE_DIR and come up quickly. start them now but
  # only await them just before the e2e run, so their startup overlaps the php syntax
  # checks and the PHPUnit run below instead of adding to them.
  for SHARD in $(seq 2 "$E2E_SHARDS"); do
    ionos.wordpress.start_test_container "$SHARD"
  done
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

      echo "$SYNTAX_CHECK_OUTPUT"

      if [[ $SYNTAX_CHECK_STATUS -ne 0 ]] || [[ -z "$SYNTAX_CHECK_OUTPUT" ]] || echo "$SYNTAX_CHECK_OUTPUT" | grep -qv '^No syntax errors'; then
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
  # the shards started back before the PHPUnit run should be up by now - make sure
  E2E_SHARD_NAMES=()
  for SHARD in $(seq 1 "$E2E_SHARDS"); do
    [[ "$SHARD" == '1' ]] || ionos.wordpress.await_test_container "$SHARD"
    E2E_SHARD_NAMES+=("$(ionos.wordpress.test_container_name "$SHARD")")
  done

  for SHARD_NAME in "${E2E_SHARD_NAMES[@]}"; do
    # next 2 steps are required since a preceding phpunit run resets the database to a
    # state that is not suitable for e2e tests
    # set the default admin password to the password defined in .env file
    docker exec --user php "$SHARD_NAME" wp --quiet user update admin --user_pass="${WP_PASSWORD}" --path=/htdocs
    # reset the user meta for compromised credentials check
    docker exec --user php "$SHARD_NAME" wp --quiet user meta delete admin ionos_compromised_credentials_check_leak_detected_v2 --path=/htdocs &>/dev/null || true
  done

  # run e2e tests against the ephemeral test containers' published ports. provide part
  # specific options and all positional arguments that are php files.
  #
  # with E2E_SHARDS=1 this is exactly the previous single invocation. above that, one
  # playwright process per shard runs concurrently, each pinned to its own container via
  # WP_BASE_URL (page navigation) and TEST_CONTAINER_NAME (the wp-cli calls the specs
  # make through playwright/exec-test-cli.js). E2E_SHARD_INDEX keeps their storage
  # states, output dirs and html reports apart - see playwright.config.js.
  E2E_PIDS=()
  for SHARD in $(seq 1 "$E2E_SHARDS"); do
    (
      SHARD_SUFFIX=''
      [[ "$E2E_SHARDS" == '1' ]] || SHARD_SUFFIX="-${SHARD}"

      export WP_BASE_URL="http://localhost:$(ionos.wordpress.test_container_port "$SHARD")"
      export TEST_CONTAINER_NAME="$(ionos.wordpress.test_container_name "$SHARD")"
      [[ "$E2E_SHARDS" == '1' ]] || export E2E_SHARD_INDEX="$SHARD"

      # @wordpress/e2e-test-utils-playwright builds its worker-scoped 'requestUtils'
      # fixture around a module-level STORAGE_STATE_PATH (defaulting to
      # <cwd>/artifacts/storage-states/admin.json). that is the file the specs' calls to
      # requestUtils.setupRest() rewrite to restore the login state for the following
      # test - so concurrent shards MUST NOT share it, or they overwrite each other's
      # cookies (which are bound to their own container's port) and every test after the
      # first setupRest() lands back on wp-login.php. pointing it at the very same file
      # playwright.config.js hands the browser context also keeps the two in sync, so the
      # refreshed cookies are actually the ones the next test starts from.
      export WP_ARTIFACTS_PATH="$(pwd)/playwright/e2e/.artifacts${SHARD_SUFFIX}"
      export STORAGE_STATE_PATH="$(pwd)/playwright/e2e/.storage-states/admin${SHARD_SUFFIX}.json"
      pnpm exec playwright test --pass-with-no-tests -c ./playwright.config.js \
        $([[ "$E2E_SHARDS" == '1' ]] || printf -- '--shard=%s/%s' "$SHARD" "$E2E_SHARDS") \
        ${USE_OPTIONS[e2e]:---quiet} \
        $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.js ]] && printf "$file "; done)
    ) &
    E2E_PIDS+=("$!")
  done

  # collect every shard before deciding the outcome - never bail on the first failure,
  # or a red shard would leave the others orphaned and their containers half torn down
  E2E_EXIT=0
  for E2E_PID in "${E2E_PIDS[@]}"; do
    wait "$E2E_PID" || E2E_EXIT=1
  done
  [[ "$E2E_EXIT" -eq 0 ]] || exit "$E2E_EXIT"
fi

exit

###help-message
Syntax: 'pnpm run test [options] [additional-args]'

Executes tests.

If PHPUnit or e2e tests will be run, a throwaway wordpress-alpine test container is started
and torn down again afterwards (pass or fail).

Environment variables:

  E2E_SHARDS  Number of parallel e2e shards (default: 1).

              The e2e specs set up global WordPress state in beforeAll and contradict
              each other, so they cannot share one instance. Each shard therefore gets
              its own throwaway container (own name, own published port, own wp-content
              overlay) and playwright splits the spec files across them with --shard.

              Ignored (forced to 1) when individual test files are passed as arguments.

              Example - run the e2e suite across 3 containers:
                'E2E_SHARDS=3 pnpm run test --use e2e'

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

