import { execSync } from 'child_process';

// the ephemeral test container started by scripts/test.sh (see
// scripts/includes/_docker-mounts.sh) - a single fixed name now, no more per-run
// wp-env container discovery.
const CONTAINER_NAME = 'ionos-wordpress-test';

export function execTestCLI(command) {
  return execSync(`cat <<EOF | docker exec --interactive --user php ${CONTAINER_NAME} sh -
    set -x
    ${command}
EOF`)
    .toString()
    .trim();
}
