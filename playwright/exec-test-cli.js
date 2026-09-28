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

// wp-admin/REST activity (theme/plugin activation, Heartbeat polling from a still-open
// wp-admin browser context, etc.) can still be writing to wp_options/wp_posts/... at the exact
// moment dumpTestDb()/restoreTestDb() below run their DROP+CREATE+INSERT reload - reloading a
// table is not one atomic statement, so a write landing mid-reload can collide with a row the
// snapshot itself is inserting ("ERROR 1062 Duplicate entry ... for key 'option_name'"). This is
// a one-off timing collision, not a deterministic bug, so a couple of quick retries clears it
// instead of failing (or forcing Playwright to re-run the entire, much slower, test).
function execWithRetry(command, retries = 3, delayMs = 500) {
  for (let attempt = 1; ; attempt++) {
    try {
      return execSync(command);
    } catch (error) {
      const isDuplicateEntryRace = /ERROR 1062 \(23000\)/.test(
        `${error.stdout ?? ''}${error.stderr ?? ''}${error.message ?? ''}`
      );
      if (!isDuplicateEntryRace || attempt >= retries) {
        throw error;
      }
      execSync(`sleep ${delayMs / 1000}`);
    }
  }
}

// dumps the current 'wordpress' database to a snapshot file inside the test container. Call
// once (from global-setup.js) right after the environment has been reset to the state every
// e2e test should start from - restoreTestDb() below reloads exactly that state.
//
// --single-transaction: gives the dump one consistent InnoDB MVCC snapshot instead of an
// unlocked read, so it can't itself capture a half-written row - reduces, but (see
// execWithRetry above) does not eliminate, the write-during-reload race.
export function dumpTestDb() {
  execWithRetry(
    `docker exec --user php ${CONTAINER_NAME} sh -c 'mariadb-dump --single-transaction ${DB_CREDENTIALS} --result-file=${DB_SNAPSHOT_PATH}'`
  );
}

// reloads the snapshot taken by dumpTestDb(), discarding whatever the previous test did to the
// database. Run before each test (not after) so a crashed/timed-out test never leaves the
// database dirty for the one that follows.
export function restoreTestDb() {
  execWithRetry(`docker exec --user php ${CONTAINER_NAME} sh -c 'mariadb ${DB_CREDENTIALS} < ${DB_SNAPSHOT_PATH}'`);
}
