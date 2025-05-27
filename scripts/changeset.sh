#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script executes the changeset tool
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

if [[ "$@" == '' ]]; then
  # if no arguments are passed, show help
  # prefix 'changeset' with 'pnpm ' in output to avoid user confusion
  pnpm exec changeset --help | sed 's/\$ \(changeset \)/pnpm \1/g'
  exit 0
fi

# if versioning occured process to update the new version numbers in other files
if [[ "$1" == 'version' ]]; then
  # 'changeset version' doesnt abort with error code if no changesets are found
  # thats why we abort
  #   if 'changeset version' spits out 'No unreleased changesets found' on stderr
  pnpm exec changeset version 2>&1 | tee /dev/stderr | grep -q -v 'No unreleased changesets found'
else
  pnpm exec changeset $@
fi

# we need to keep the lockfile in sync with the updated package.json files
pnpm install





