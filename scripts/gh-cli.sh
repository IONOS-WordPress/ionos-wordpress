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

exec gh "$@"


