#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script executes the changeset tool
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/_bootstrap.sh"

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
  #
  # note: the output is buffered into a variable (instead of piping into `grep -q`)
  # because `grep -q` quits on its first match and closes its stdin, which would send
  # SIGPIPE upstream and abort this script early (exit 141) due to `set -o pipefail`
  output="$(pnpm exec changeset version 2>&1)"
  echo "$output"
  if grep -q 'No unreleased changesets found' <<< "$output"; then
    exit 1
  fi
else
  pnpm exec changeset $@
fi

# we need to keep the lockfile in sync with the updated package.json files
pnpm install





