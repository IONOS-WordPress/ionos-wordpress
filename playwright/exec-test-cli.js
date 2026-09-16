//
// runs a wp-cli command inside the ephemeral test container from a Playwright spec,
// replacing the old playwright/wp-env.js
//

import { execSync } from 'child_process';

// the ephemeral test container started by scripts/test.sh (see
// scripts/includes/_docker-mounts.sh) - no more per-run wp-env container discovery.
const CONTAINER_NAME = process.env.TEST_CONTAINER_NAME || 'ionos-wordpress-test';

export function execTestCLI(command) {
  return execSync(`cat <<'EOF' | docker exec --interactive --user php ${CONTAINER_NAME} sh -
    set -x
    ${command}
EOF`)
    .toString()
    .trim();
}

// path inside the container, not on the host - the snapshot never needs to leave it.
const DB_SNAPSHOT_PATH = '/tmp/e2e-db-snapshot.sql';

// the db user scripts/test.sh passes into the container (--env WORDPRESS_DB_USER/PASSWORD/NAME)
// already has all privileges on its database - no need for root here. the variables are left
// unexpanded on purpose: they are resolved by the shell *inside* the container, so the
// credentials live in exactly one place per run instead of being duplicated here.
const DB_CREDENTIALS = '-u "$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" "$WORDPRESS_DB_NAME"';

// dumps the current 'wordpress' database to a snapshot file inside the test container. Call
// once (from global-setup.js) right after the environment has been reset to the state every
// e2e test should start from - restoreTestDb() below reloads exactly that state.
export function dumpTestDb() {
  execSync(
    `docker exec --user php ${CONTAINER_NAME} sh -c 'mariadb-dump ${DB_CREDENTIALS} --result-file=${DB_SNAPSHOT_PATH}'`
  );
}

// reloads the snapshot taken by dumpTestDb(), discarding whatever the previous test did to the
// database. Run before each test (not after) so a crashed/timed-out test never leaves the
// database dirty for the one that follows.
export function restoreTestDb() {
  execSync(`docker exec --user php ${CONTAINER_NAME} sh -c 'mariadb ${DB_CREDENTIALS} < ${DB_SNAPSHOT_PATH}'`);
}
