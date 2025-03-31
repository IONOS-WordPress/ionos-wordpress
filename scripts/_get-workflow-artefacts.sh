#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to compute all workflow distributable artifacts of the workspace packages
# this includes dist/*{.zip,.tgz} files and playwright test results for example
#
# the script is intended to be used in a CI/CD environment to collect all workflow artifacts
#
# it requires a successful build of the workspace packages to get correct results
#
# you can test it by executing `pnpm build` and afterwards `pnpm exec ./scripts/_get-workflow-artefacts.sh`
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

#
# outputs the workflow distributable artifacts of all workspace packages
# the workspace needs to be built to get correct results
#
# workflow distributable artifacts are
# - workspace package flavor specific and can be a .zip or .tgz files usually located in the dist folder of the package
# - playwright test results if any
#
function ionos.wordpress.get_workflow_artifacts() {
  local PACKAGE_PATH PACKAGE_NAME FLAVOUR ARTIFACTS=()

  # add plawright test results if any
  test -d ./playwright/storybook/.playwright-report/ && ARTIFACTS+=(./playwright/storybook/.playwright-report/)
  test -d ./playwright/storybook/.test-results/ && ARTIFACTS+=(./playwright/storybook/.test-results/)
  test -d ./playwright/e2e/.playwright-report/ && ARTIFACTS+=(./playwright/e2e/.playwright-report/)
  test -d ./playwright/e2e/.test-results/ && ARTIFACTS+=(./playwright/e2e/.test-results/)

  # loop over workspace packages and grab flavor specific artifacts
  for PACKAGE_PATH in $(find ./packages -mindepth 2 -maxdepth 2 -type d | sort); do
    PACKAGE_NAME=$(jq -r '.name // false' $PACKAGE_PATH/package.json)

    if [[ "$(jq -r '.private // false' $PACKAGE_PATH/package.json)" == "true" ]]; then
      ionos.wordpress.log_warn "skipping package $PACKAGE_NAME - it is marked as private"
      continue
    fi

    FLAVOUR=$(basename $(dirname $PACKAGE_PATH))
    case "$FLAVOUR" in
      docker)
        ionos.wordpress.log_warn "skipping $FLAVOUR package $PACKAGE_NAME - docker packages are not distributable"
        ;;
      npm)
        ARTIFACTS+=("$(find $PACKAGE_PATH/dist -type f -name '*.tgz' 2>/dev/null || \
          ionos.wordpress.log_warn "skip collecting build artifacts in $PACKAGE_PATH/dist : directory does not exist" >&2)")
        ;;
      wp-plugin|wp-theme)
        ARTIFACTS+=("$(find $PACKAGE_PATH/dist -type f -name '*.zip'  || \
          ionos.wordpress.log_warn "skip collecting build artifacts in $PACKAGE_PATH/dist : directory does not exist" >&2)")
        ;;
      *)
        ionos.wordpress.log_error "don't know how to handle workspace package flavor '$FLAVOUR' (extracted from path=$PACKAGE_PATH)"
        exit 1
        ;;
    esac
  done

  # convert array into newline separated string of items
  printf -v ARTIFACTS "%s\n" "${ARTIFACTS[@]}"

  # if artifacts not empty print artifacts to stdout and remove trailing newline
  [[ -n "$ARTIFACTS" ]] && echo "${ARTIFACTS%$'\n'}"
}

# execute function
ionos.wordpress.get_workflow_artifacts
