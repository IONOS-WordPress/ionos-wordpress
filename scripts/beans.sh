#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm beans ...` instead or call it as package script.
#
# this script downloads the `beans` CLI (https://github.com/hmans/beans) - a flat-file issue tracker
# for humans and coding agents - if it's not already installed, and then executes it.
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

BEANS_VERSION="0.4.2"

if [[ ! -f ./bin/beans ]]; then
  OS="$(uname -s)"
  ARCH="$(uname -m)"
  [[ "$ARCH" == "aarch64" ]] && ARCH="arm64"

  ASSET="beans_${OS}_${ARCH}.tar.gz"
  RELEASE_URL="https://github.com/hmans/beans/releases/download/v${BEANS_VERSION}"

  mkdir -p ./bin
  TMP_DIR="$(mktemp -d)"
  trap 'rm -rf "$TMP_DIR"' EXIT

  ionos.wordpress.log_info "Downloading beans ${BEANS_VERSION} from ${RELEASE_URL}/${ASSET}"

  if ! curl -Ls --fail -o "$TMP_DIR/$ASSET" "$RELEASE_URL/$ASSET"; then
    ionos.wordpress.log_error "Error: Failed to download beans from $RELEASE_URL/$ASSET (unsupported OS/ARCH: ${OS}/${ARCH}?)"
    exit 1
  fi

  if ! curl -Ls --fail -o "$TMP_DIR/checksums.txt" "$RELEASE_URL/beans_${BEANS_VERSION}_checksums.txt"; then
    ionos.wordpress.log_error "Error: Failed to download beans checksums from $RELEASE_URL/beans_${BEANS_VERSION}_checksums.txt"
    exit 1
  fi

  if ! (cd "$TMP_DIR" && grep "$ASSET\$" checksums.txt | sha256sum -c -); then
    ionos.wordpress.log_error "Error: beans checksum verification failed"
    exit 1
  fi

  tar -xzf "$TMP_DIR/$ASSET" -C "$TMP_DIR" beans
  mv "$TMP_DIR/beans" ./bin/beans
  chmod +x ./bin/beans

  ionos.wordpress.log_info "Installed beans ${BEANS_VERSION} successfully in ./bin/beans"
fi

exec ./bin/beans "$@"
