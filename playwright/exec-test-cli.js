import { execSync } from 'child_process';

// the ephemeral test container started by scripts/test.sh (see
// scripts/includes/_docker-mounts.sh) - no more per-run wp-env container discovery.
//
// when the e2e run is sharded (E2E_SHARDS > 1) every shard drives its own container, and
// scripts/test.sh exports TEST_CONTAINER_NAME so the wp-cli calls a spec makes here land
// in the same WordPress the spec is browsing. unsharded runs fall back to the historical
// fixed name.
const CONTAINER_NAME = process.env.TEST_CONTAINER_NAME || 'ionos-wordpress-test';

export function execTestCLI(command) {
  return execSync(`cat <<'EOF' | docker exec --interactive --user php ${CONTAINER_NAME} sh -
    set -x
    ${command}
EOF`)
    .toString()
    .trim();
}
