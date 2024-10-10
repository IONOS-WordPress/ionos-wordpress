#!/usr/bin/env bash

#
# this file will be executed when the feature gets installed
#

# fail if any following command fails
set -eo pipefail

export DEBIAN_FRONTEND=noninteractive

MESSAGE="${MESSAGE:-""}"

# install welcome message file
if [ ! -f /usr/local/etc/vscode-dev-containers/first-run-notice.txt ]; then
	echo "Installing First Run Notice..."
  # create message to show at first vscode run
  # COPY .devcontainer/welcome.txt /usr/local/etc/vscode-dev-containers/first-run-notice.txt
  cat <<EOF | sudo tee /usr/local/etc/vscode-dev-containers/first-run-notice.txt
# Setup

At first time you need to install the dependencies using the "pnpm install --frozen-lockfile" command.

# Development

Run "pnpm start" to start the WordPress development environment.

Execute "jq '.scripts' package.json" to see available scripts.

Have fun !

EOF
fi
