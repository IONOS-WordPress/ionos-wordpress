#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script can be used to access a user webspace using go-waas
#
# the script will download go-waas from the gitlab.git-wp.server.lan server if it's not already installed
#
# example usage:
#   pnpm go-waas wpscantest-g3bj3z5gzj.live-website.com --tenant=strato
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

if [[ ! -f ./bin/go-waas ]]; then
  if ! ping -c 1 gitlab.git-wp.server.lan &> /dev/null; then
    ionos.wordpress.log_error 'Error: gitlab.git-wp.server.lan is not reachable.'

  cat <<'EOF'

Check if your VPN is connected and you can reach the gitlab.git-wp.server.lan server.

EOF
    exit 1
  fi

  mkdir -p ./bin

  # see https://gitlab.git-wp.server.lan/whappdev/gowaas/-/releases

  ASSET_URL="https://gitlab.git-wp.server.lan/whappdev/gowaas/-/releases/permalink/latest/downloads/go-waas_$(uname -s)_$(uname -m)"

  ionos.wordpress.log_info "Downloading go-waas from ${ASSET_URL}"

  if ! curl -Ls --fail -o ./bin/go-waas "$ASSET_URL"; then
    ionos.wordpress.log_error "Error: Failed to download go-waas from $ASSET_URL"
    exit 1
  fi
  chmod +x ./bin/go-waas

  ionos.wordpress.log_info "Installed go-waas successfully in ./bin/go-waas"
fi

exec ./bin/go-waas "$@"


