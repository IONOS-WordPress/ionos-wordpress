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

# test file arguments
POSITIONAL_ARGS=()

# array of flags indicating which kind of tests (react,php,...) to execute
USE=()

# options per kind of test
declare -A USE_OPTIONS

while [[ $# -gt 0 ]]; do
  case $1 in
    --help)
      # print everythin in this script file after the '###help-message' marker
      printf "$(sed -e '1,/^###help-message/d' "$0")"
      echo $0
      exit 0
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

function ionos.wordpress.prepare_playwright_environment() {
    # used to prevent wp-scripts test-playwright command from downloading browsers
  export PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1
  # we need to inject the path to the installed chrome binary
  # via PLAYWRIGHT_CHROME_PATH
  export PLAYWRIGHT_CHROME_PATH=$(find ~/.cache/ms-playwright -path "*/chrome-linux/chrome" 2>/dev/null)

  if [[ "$PLAYWRIGHT_CHROME_PATH" == '' ]]; then
    ionos.wordpress.log_error 'Playwright chromium path not found (test: find ~/.cache/ms-playwright -path "*/chrome-linux/chrome").'
    ionos.wordpress.log_error 'Please install it manually by running "PLAYWRIGHT_DOWNLOAD_CONNECTION_TIMEOUT=10000 pnpx playwright install --with-deps chromium"'
    exit 1
  fi
}

if [[ "${USE[@]}" =~ all|react ]]; then
  # MARK: ensure the playwright cache is generated in the same environment (devcontainer or local) as the tests are executed
  # (this is necessary because the cache is not portable between environments)
  PLAYWRIGHT_DIR=$(realpath ./playwright)
  if [[ -f "$PLAYWRIGHT_DIR/.cache/metainfo.json" ]] && ! grep "$PLAYWRIGHT_DIR" ./playwright/.cache/metainfo.json > /dev/null; then
    # ./playwright/.cache/metainfo.json contains not the absolute path to the cache directory of the current environment
    rm -rf "$PLAYWRIGHT_DIR/.cache"
  fi
  # ENDMARK

  (
    ionos.wordpress.prepare_playwright_environment

    # execute playwright tests. provide part specific options and all positional arguments that are jsx files
    pnpm exec playwright test -c ./playwright-ct.config.js \
      "${USE_OPTIONS[react]:---quiet}" \
      $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.jsx ]] && printf "$file "; done)
  )
fi

if [[ "${USE[@]}" =~ all|php|e2e ]]; then
  # MARK: ensure wp-env started
  # ensure wp-env is running
  # - if the install path does not exist
  # - or if the containers are not running
  WPENV_INSTALLPATH="$(realpath --relative-to $(pwd) $(pnpm exec wp-env install-path))"
  if [[ ! -d "$WPENV_INSTALLPATH/WordPress" ]] || [[ "$(docker ps -q --filter "name=$(basename $WPENV_INSTALLPATH)" | wc -l)" -lt '6' ]]; then
    pnpm start
  fi
  # ENDMARK
fi

if [[ "${USE[@]}" =~ all|php ]]; then
  # start wp-env unit tests. provide part specific options and all positional arguments that are php files
  # (files will be converted to '--filter *TestCase' arguments to match PHPUNit expectations)
  pnpm -s run wp-env run tests-wordpress phpunit -- \
    "${USE_OPTIONS[php]}" \
    $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.php ]] && printf -- "--filter '%s'" $(basename $file .php); done)
fi

if [[ "${USE[@]}" =~ all|e2e ]]; then
  # start wp-env e2e tests. provide part specific options and all positional arguments that are php files
  (
    ionos.wordpress.prepare_playwright_environment
    pnpm exec wp-scripts test-playwright -c ./playwright.config.js \
      "${USE_OPTIONS[e2e]:---quiet}" \
      $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.js ]] && printf "$file "; done)
  )
fi

exit

###help-message
Syntax: 'pnpm run test [options] [additional-args]'

Executes tests.

if PHPUnit or e2e tests will be runned, it will start wp-env if not already running.

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

  Usages:
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
      'pnpm run test:e2e packages/wp-plugin/essentials/inc/dashboard/tests/e2e/deep-links-block.spec.js' or
      'pnpm run test:e2e deep-links-block.spec.js' (path can be left off for playwright)

    Execute only a single e2e test file with playwright debugger :
      'pnpm run test:e2e --e2e-opts '--debug' packages/wp-plugin/essentials/inc/dashboard/tests/e2e/deep-links-block.spec.js' or
      'pnpm run test:e2e --e2e-opts '--debug' deep-links-block.spec.js' (path can be left off for playwright)

    Execute only PHPUnit and E2e tests:
      'pnpm run test --use e2e --use php'

see ./docs/5-test.md for more informations

