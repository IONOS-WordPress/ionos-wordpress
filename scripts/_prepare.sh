#!/usr/bin/env bash

#
# script installs the git hooks for linting on pre commit
#
# this script is used to build all packages of the monorepo
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# execute only when NOT in CI environment
if [[ "${CI:-}" == "true" ]]; then
  ionos.wordpress.log_warn "CI environment detected - skip setting up git hooks"
  exit 0
fi


ionos.wordpress.log_info "Setting up git hooks"
git config core.hookspath "./.githooks"
