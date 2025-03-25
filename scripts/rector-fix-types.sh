#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used fix/upgrade our php code using rector.
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

docker run \
  $DOCKER_FLAGS \
  --rm \
  --user "$DOCKER_USER" \
  -v $(pwd)/packages/wp-plugin/essentials:/project/dist \
  -v $(pwd)/packages/docker/rector-php/rector-fix-types.php:/project/rector-fix-types.php \
  ionos-wordpress/rector-php \
  --clear-cache \
  --config "rector-fix-types.php" \
  --no-progress-bar \
  process \
  .

