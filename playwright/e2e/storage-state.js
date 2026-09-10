//
// shared storage-state path constants - used by playwright.config.js, global-setup.js and
// fixtures.js so all three agree on where the browser's login cookies live on disk.
//

export const STORAGE_STATE_PATH = process.env.STORAGE_STATE_PATH || './playwright/e2e/.storage-states/admin.json';

// the storageState file as it looked right after global-setup.js logged in and dumpTestDb()
// snapshotted the database - fixtures.js restores both together before each test, so a spec
// that calls requestUtils.setupRest() (rotating the session token in the DB and rewriting this
// file) never leaves the next test with a cookie/DB session mismatch.
export const STORAGE_STATE_SNAPSHOT_PATH = `${STORAGE_STATE_PATH}.snapshot`;
