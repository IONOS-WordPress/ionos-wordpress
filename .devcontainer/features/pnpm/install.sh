#!/usr/bin/env bash

#
# this file will be executed when the feature gets installed
#

# fail if any following command fails
set -eo pipefail

PNPM_VERSION="${VERSION:-"latest"}"

# install pnpm
curl https://get.pnpm.io/install.sh | \
  sudo -u "$USERNAME" ENV="$HOME/.bashrc" PNPM_VERSION="$PNPM_VERSION" SHELL="$(which bash)" bash -
