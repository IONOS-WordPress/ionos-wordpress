#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is a wrapper for the pnpm update
#
# example usage: `pnpm update-dependencies` or `pnpm update-dependencies --pnpm-opts '--latest'`
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/_bootstrap.sh"

function ionos.wordpress.update_package_dependencies() {
  # interactive updates of catalogs doesnt work yet with pnpm : https://github.com/pnpm/pnpm/issues/8566
  #
  # > The pnpm update command does not yet support catalogs.
  # > To update dependencies defined in pnpm-workspace.yaml, newer version ranges will need to be chosen manually until a future version of pnpm handles this.
  #
  # pnpm --recursive update --interactive $@

  # update dependencies
  pnpm --recursive update $@

  # if package.json was changed by pnpm update, update pnpm-lock.yaml
  if [[ $(git status --porcelain | grep "package.json") ]]; then
    # update pnpm-lock.yaml file and install updated dependencies
    pnpm install
    ionos.wordpress.log_warn "Updated dependencies successfully."
    ionos.wordpress.log_warn "Consider running 'pnpm build' to rebuild the project using the updated dependencies."
  fi
}

# check if used nodejs version is still latest lts
function ionos.wordpress.check_nodejs_updates() {
  CURRENT_NODEJS_VERSION=$(pnpm exec node -v | tr -d 'v')
  LATEST_LTS_NODEJS_VERSION=$(curl -sL https://nodejs.org/dist/index.json | jq -r '[.[] | select(.lts != false)][0].version' | tr -d 'v')

  if [[ "$CURRENT_NODEJS_VERSION" != "$LATEST_LTS_NODEJS_VERSION" ]]; then
    ionos.wordpress.log_warn "Node.js version can be updated ($CURRENT_NODEJS_VERSION => $LATEST_LTS_NODEJS_VERSION) manually."
    echo "GIT managed files potentially referencing the current NodeJS version '$CURRENT_NODEJS_VERSION' are :"
    git grep -w "${CURRENT_NODEJS_VERSION}"
  fi
}

# fetch a GitHub API endpoint, printing nothing and returning non-zero if the response isn't a JSON array
# (e.g. a rate-limit error object) so callers can skip gracefully instead of feeding jq bad input
function ionos.wordpress.fetch_github_tags() {
  local response
  if command -v gh >/dev/null 2>&1 && gh auth status >/dev/null 2>&1; then
    # use gh's auth token to avoid the unauthenticated 60 req/hour rate limit
    response=$(gh api "$(sed -e 's#https://api.github.com/##' <<<"$1")" 2>/dev/null || true)
  else
    response=$(curl -Ls "$1" 2>/dev/null || true)
  fi
  if ! jq -e 'type == "array"' >/dev/null 2>&1 <<<"$response"; then
    ionos.wordpress.log_warn "GitHub API request to '$1' did not return a tag list (rate limited?) - skipping version check."
    return 1
  fi
  echo "$response"
}

# check pnpm is up to date
function ionos.wordpress.check_pnpm_version() {
  CURRENT_PNPM_VERSION=$(pnpm --version)
  local tags
  tags=$(ionos.wordpress.fetch_github_tags https://api.github.com/repos/pnpm/pnpm/tags) || return 0
  LATEST_PNPM_VERSION=$(jq -r '.[] | .name | select(test("^v([0-9]+\\.){2}[0-9]+$"))' <<<"$tags" | head -n 1 | tr -d 'v')

  if [[ "$CURRENT_PNPM_VERSION" != "$LATEST_PNPM_VERSION" ]]; then
    ionos.wordpress.log_warn "pnpm version can be updated ($CURRENT_PNPM_VERSION => $LATEST_PNPM_VERSION) manually."
    echo "GIT managed files potentially referencing the current pnpm version '$CURRENT_PNPM_VERSION' are :"
    git grep -w "${CURRENT_PNPM_VERSION}" || echo "No matches found - your current pnpm version is different from desired version $CURRENT_PNPM_VERSION."
  fi
}

# check docker is up to date
function ionos.wordpress.check_docker_version() {
  CURRENT_DOCKER_VERSION=$(docker version --format '{{.Client.Version}}')
  local tags
  tags=$(ionos.wordpress.fetch_github_tags https://api.github.com/repos/docker/cli/tags) || return 0
  LATEST_DOCKER_VERSION=$(jq -r '.[] | .name | select(test("^v([0-9]+\\.){2}[0-9]+$"))' <<<"$tags" | head -n 1 | tr -d 'v')

  if [[ "$CURRENT_DOCKER_VERSION" != "$LATEST_DOCKER_VERSION" ]]; then
    ionos.wordpress.log_warn "docker version can be updated ($CURRENT_DOCKER_VERSION => $LATEST_DOCKER_VERSION) manually."
    echo "GIT managed files potentially referencing docker current pnpm version '$CURRENT_DOCKER_VERSION' are :"
    git grep -w "${CURRENT_DOCKER_VERSION}" || echo "No docker version references found - it's up to you to update your docker installation in your host system."
  fi
}

while [[ $# -gt 0 ]]; do
  case $1 in
    --help)
      ionos.wordpress.print_help "$0"
      ;;
    --pnpm-opts)
      PNPM_OPTS=$2
      shift 2
      ;;
    *)
      echo "Unknown option $1"
      exit 1
      ;;
  esac
done

function ionos.wordpress.update_composer_dependencies() {
  # find all composer.json files and run "composer update" on them
  for composer_json in $(find ./packages -name composer.json -not -path "*/node_modules/*" -and -not -path "*/vendor/*"); do
    ionos.wordpress.log_header "checking '$composer_json' for updates ..."
    (
      cd "$(dirname $composer_json)"
      docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:latest update --no-install --no-scripts
      docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:latest outdated --locked --direct
    )
  done
}

ionos.wordpress.update_package_dependencies "${PNPM_OPTS:-}"
ionos.wordpress.check_nodejs_updates
ionos.wordpress.check_pnpm_version
ionos.wordpress.check_docker_version
ionos.wordpress.update_composer_dependencies

# execute optional "update-dependencies" script targets of individual workspace packages
for package_path in $(find packages -mindepth 2 -maxdepth 2 -type d); do
  PACKAGE_NAME=$(jq -r '.name' $package_path/package.json)
  PACKAGE_SCRIPT_UPDATE_DEPENDENCIES=$(jq -r '.scripts."update-dependencies" // ""' $package_path/package.json)
  if [[ "$PACKAGE_SCRIPT_UPDATE_DEPENDENCIES" != '' ]]; then
    pnpm -s --filter "$PACKAGE_NAME" run update-dependencies
  fi
done

# check for malicious packages using safedep vet
# --pull=always is the cheapest way to ensure latest vet database is used
docker run $DOCKER_FLAGS --pull=always --rm -v $(pwd):/workspace ghcr.io/safedep/vet:latest scan -D /workspace

exit

###help-message
Syntax: 'pnpm run update-dependencies [options] [additional-args]'

Checks for updates of

- package dependencies
- nodejs version
- pnpm version
- composer dependencies
- docker version
- updates in workspace packages of the 'docker' flavour

Options:

  --help                    Show this help message and exit

  --pnpm-opts '<pnpm-opts>'   Pass additional options to pnpm command

Usage :

  Update package dependencies
  `pnpm update-dependencies'

  Update package dependencies to latest version
  'pnpm update-dependencies --pnpm-opts "--latest"'

See ./docs/3-tools.md for more informations
