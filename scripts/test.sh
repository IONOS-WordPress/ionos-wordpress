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
      cat <<EOF
Usage: $0 [options] [-- additional-args]"

Executes tests.

This action will start wp-env if it is not already running.

Options:

  --help    Show this help message and exit

  --use     Specify which tests to execute (default: all)

            Available options:
              - php       execute PHPUnit tests
              - e2e       execute E2E tests
              - react     execute Storybook/React tests

            This option can be used multiple times to specify multiple tests.

  Example usage :
    Execute only PHPUnit and E2e tests: 'pnpm run test --use e2e --use php'

    Execute PHPUnit tests and provide additional args to PHPUnit :

      'pnpm test --use php -- --filter test_my_test_method'

      'pnpm test --use php -- --filter MyTestClass'

      'pnpm run test --use php -- --group foo'
EOF
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
      "${USE_OPTIONS[react]}" \
      $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.jsx ]] && printf "$file"; done)
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
  # start wp-env unit tests. provide part specific options and all positional arguments that are jsx files
  pnpm run wp-env run tests-wordpress phpunit \
    "${USE_OPTIONS[php]}" \
    $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.php ]] && printf "$file"; done)
fi

if [[ "${USE[@]}" =~ all|e2e ]]; then
  # start wp-env e2e tests
  (
    ionos.wordpress.prepare_playwright_environment
    pnpm exec wp-scripts test-playwright -c ./playwright.config.js "${POSITIONAL_ARGS[@]}"
  )
fi
