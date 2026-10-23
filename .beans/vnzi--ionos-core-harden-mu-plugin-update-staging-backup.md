---
# vnzi
title: 'ionos-core: harden mu-plugin update (staging, backup, rollback)'
status: completed
type: task
priority: normal
created_at: 2026-10-21T12:54:26Z
updated_at: 2026-10-21T13:02:19Z
---

Verify and harden MU_Plugin_Upgrader: lint, real fatal rollback test, cross-filesystem move, non-direct filesystem guard, package version check. Core install_package/clear_destination evaluated and rejected (would wipe WPMU_PLUGIN_DIR).

## Summary of Changes

- MU_Plugin_Upgrader::install(): shutdown-armed rollback, move_dir()-based moves, disk space check, maintenance mode around the swap, rejects non-direct filesystems and packages without version header
- tests incl. a real fatal error in a child process (fixtures/fatal-during-swap.php)
- core install_package()/clear_destination rejected: WPMU_PLUGIN_DIR is not a protected directory, it would wipe all mu-plugins
- not covered: SIGKILL / server crash leaves .ionos-core-backup-* behind (no self-healing)

## Review follow-up (PR #961 Copilot comments)

- package entries are type-checked (`ionos-core` directory, `ionos-core.php` regular file) for package and staging
- `backed_up`/`installed` are recorded before the move; rollback decides from the actual disk state, deletes attempted install destinations first and keeps the backup whenever restoration is unconfirmed
- regression tests: wrong entry types, interruption after backup move, partial destination, interruption right after install rename
