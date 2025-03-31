#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script can be used to access github cli
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

if [[ -z "$GH_TOKEN" ]]; then
  ionos.wordpress.log_error 'Error: GH_TOKEN is not set.'
  cat <<'EOF'

GH_TOKEN is required to authenticate with GitHub using GitHub CLI.

It's expected to be set in the environment in a `.secrets` file (see `.secrets.example`).

The token MUST BE a GitHub personal access token (classic) : https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens

See https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry#authenticating-with-a-personal-access-token-classic for reasons why the classic token type is required_

EOF

  exit 0
fi

if ! command -v gh &> /dev/null; then
  ionos.wordpress.log_error 'Error: GitHub CLI (gh) is not installed or not in PATH.'

  cat <<'EOF'

GitHub CLI (gh) is required to run this script. Script is automatically installed in the DevContainer

If you really want to use it locally you can install it from https://cli.github.com/

To install latest in Linux :

(type -p wget >/dev/null || (sudo apt update && sudo apt-get install wget -y)) \
  && sudo mkdir -p -m 755 /etc/apt/keyrings \
  && wget -qO- https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo tee /etc/apt/keyrings/githubcli-archive-keyring.gpg > /dev/null \
  && sudo chmod go+r /etc/apt/keyrings/githubcli-archive-keyring.gpg \
  && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
  && sudo apt update \
  && sudo apt install gh entr -y
EOF
  exit 1
fi

exec gh "$@"


