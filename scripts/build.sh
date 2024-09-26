#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to build all packages of the monorepo
#


# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

pnpm --recursive --if-present build

