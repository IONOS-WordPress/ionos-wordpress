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
  [[ $(find ~/.cache/ms-playwright -path "*/chrome-linux/chrome" 2>/dev/null | wc -l) -eq "0" ]] && pnpm exec playwright install chromium
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
find "$transpiled_plugin_dir" -name "*.php" -not -name "*Test.php" -print0 | xargs -0L1 php -l
exit $?
EOL

      if [[ $? -ne 0 ]]; then
        exit 1
      fi
    done
  else
    ionos.wordpress.log_info "skipped target php version syntax checks since individual PHPUnit test files are provided as commandline arguments"
  fi

  # start wp-env unit tests. provide part specific options and all positional arguments that are php files
  # (files will be converted to '--filter *TestCase' arguments to match PHPUNit expectations)
  pnpm -s run wp-env run tests-wordpress phpunit -- \
    "${USE_OPTIONS[php]}" \
    $(for file in "${POSITIONAL_ARGS[@]}"; do [[ $file == *.php ]] && printf -- "--filter '%s'" $(basename $file .php); done)
fi

if [[ "${USE[@]}" =~ all|e2e ]]; then
  # set the default admin password to the password defined in .env file
  pnpm -s wp-env run tests-cli wp user update admin --user_pass="${WP_ENV_TEST_ADMIN_PASSWORD}"
  # reset the user meta for compromised credentials check
  pnpm -s wp-env run tests-cli wp user meta delete admin ionos_compromised_credentials_check_leak_detected_v2 &>/dev/null || true

  # start wp-env e2e tests. provide part specific options and all positional arguments that are php files
  (
    # pnpm exec wp-scripts test-playwright --pass-with-no-tests -c ./playwright.config.js \
    pnpm exec playwright test --pass-with-no-tests -c ./playwright.config.js \
      ${USE_OPTIONS[e2e]:---quiet} \
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

