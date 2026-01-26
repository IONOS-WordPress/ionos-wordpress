#!/usr/bin/env bash

#
# Wrapper script for stretch.sh from private repository
# https://github.com/IONOS-WordPress/ionos-wordpress-private
# Downloads the actual script on-demand and executes it
#
# This script transparently handles fetching and executing the latest version of the stretch.sh script
#
# Usage:
#   ./stretch.sh [--update] [args...]
#
#   --update : Force re-download of the script
#

set -euo pipefail

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

readonly STRETCH_IMPL="$(realpath $0 | xargs dirname)/_stretch.sh"

# Function to download the script
download_script() {
  readonly REMOTE_NAME="stretch-remote"
  readonly REMOTE_URL="git@github.com:IONOS-WordPress/ionos-wordpress-private.git"
  readonly REMOTE_BRANCH="main"
  readonly REMOTE_PATH="scripts/_stretch.sh"

  ionos.wordpress.log_warn "Checking out stretch.sh from private repository..."

  # Add remote if it doesn't exist
  if ! git remote get-url "${REMOTE_NAME}" &>/dev/null; then
    git remote add "${REMOTE_NAME}" "${REMOTE_URL}"
  fi

  # Fetch the branch (shallow)
  git fetch "${REMOTE_NAME}" "${REMOTE_BRANCH}" --depth=1 2>/dev/null

  # Extract the file
  git show "${REMOTE_NAME}/${REMOTE_BRANCH}:${REMOTE_PATH}" > "${STRETCH_IMPL}"
  chmod +x "${STRETCH_IMPL}"

  ionos.wordpress.log_warn "Successfully downloaded stretch.sh"
}

# Check for --update flag
if [[ "${1:-}" == "--update" ]]; then
  download_script
  shift
fi

# Download if not present
if [[ ! -f "${STRETCH_IMPL}" ]]; then
  download_script
fi

# Execute the actual script with all arguments
exec "${STRETCH_IMPL}" "$@"
