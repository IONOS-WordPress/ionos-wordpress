---
# v31s
title: Re-verify S3 release end to end after Copilot review fixes
status: completed
type: task
priority: normal
created_at: 2026-09-21T07:51:13Z
updated_at: 2026-09-21T09:06:01Z
parent: ig4m
---

feat/replace-github-s3 got 2 new commits (fix Copilot review findings) after the original end-to-end fork verification in 4uj1. Re-run the fork release runbook (docs/6-forking.md, docs/5-test.md) to confirm S3 delivery still works with the current branch state before opening the upstream PR.

## Summary of Changes

Discovered fork `develop` had diverged from upstream in a corrupted way (already contained the whole S3 feature branch plus a disposable release commit that collided in version number with upstream's own independently-released `essentials@1.7.1`). Per user instruction, fixed this first:

- Reset fork `develop` to exactly mirror `upstream/develop` (`git push --force origin upstream/develop:develop`), discarding the disposable release residue (the 21 S3-feature commits stay safe on `feat/replace-github-s3`).
- Merged `upstream/develop` into `feat/replace-github-s3` properly (real merge commit, not cherry-picks), resolving 2 conflicts (`.env` — additive union of `S3_FOLDER`/`WP_ENVIRONMENT_TYPE` blocks; `ionos-essentials.php` header — took upstream's real `1.7.1` version bump, kept our S3 `Update URI`). This pulled in the SIGPIPE fix (`8c67af50`) and the two prod-mode test-order fixes (`7c3cf0f5`, `23cc4aa0`) for real, superseding the disposable cherry-pick approach from the previous run.
- Deleted 3 stale releases/tags left over from the 2026-09-18 run (`essentials@1.7.1`, `ionos-core@0.5.1`, `latest`) to avoid a tag collision.
- Re-ran the fork runbook: force-pushed `feat/replace-github-s3` to fork `main`, `pre-release` succeeded (7m8s, all tests green), created `essentials@1.7.2`/`ionos-core@0.5.1` pre-releases, then triggered `release (manual workflow)` which promoted both to `@ionos-wordpress/latest` and mirrored to `s3://web-hosting/test/`.

Verified: all 8 expected S3 objects return 200 and match GitHub byte-for-byte (essentials 855042B, core 64627B); each `info.json`'s `package` field points at its own source; the shipped plugin's `Update URI` header correctly resolved `__S3_FOLDER__` to `test`; nothing written to `ionos-group/` (403 on both new version filenames). The 2 Copilot-review-fix commits from 28af39f0/787307d4 are confirmed compatible with the S3 release pipeline.

## Second release cycle: verify info.json updates correctly

Added 2 disposable changesets (`.changeset/fork-test-essentials-update-verification.md`, `.changeset/fork-test-ionos-core-update-verification.md`) on a throwaway branch built from fork `main`'s current tip - never on `feat/replace-github-s3`, confirmed untouched (`git log` shows 0 commits ahead/behind `origin/feat/replace-github-s3`, and the changeset files never existed on that branch).

Pushed to fork `main`, ran `pre-release` (7m42s, green) -> `essentials@1.7.3`/`ionos-core@0.5.2` pre-releases, then `release (manual workflow)` promoted and mirrored to S3.

Verified the update path specifically (not just first-creation):

- Both `info.json`s now report the new version (`1.7.3`/`0.5.2`) and their `package` field points at the new versioned zip - confirms the descriptor is overwritten in place on every release, not just written once.
- The previous versioned zips (`essentials-1.7.2`, `core-0.5.1`) are still reachable (200) - a real update check against an older installed version would still find its own zip if needed, and nothing got deleted.
- `-latest-` zip content-length matches the new versioned zip, confirming `latest` actually points at the new build.

## End-to-end update test against a real WordPress instance (docs/5-test.md)

Set up the isolated `TEST_PRODUCTION` stack (`ionos-wordpress-update-test`, port 8899) per the runbook, but seeded it with the actual **previously-released** versions instead of the current dev source: downloaded the real `ionos-essentials-1.7.2-php7.4.zip`/`ionos-core-0.5.1-php7.4.zip` GitHub release assets and placed their extracted content directly into each package's `dist/` output (matching `build.sh`'s own directory layout exactly), after a full `pnpm build` primed every other package's dist/zip so the stack's mount logic wouldn't error on a missing zip. Temporarily disabled the `ionos-essentials` stretch-extra provisioning entry (per the doc's own prerequisite) and rebuilt only that package.

Verified against the real, running instance:

- Confirmed via `wp-cli` that the instance actually ran the old versions (`ionos-essentials 1.7.2`, `ionos-core 0.5.1`) before touching anything.
- `wp plugin list --update=available` correctly detected `ionos-essentials` update to `1.7.3` (the version from the second fork release cycle above) - S3 answered on the first try, no GitHub fallback needed (confirmed by the resolver's log-only-on-failure behavior producing no error_log entries).
- `wp cron event run wp_update_plugins` fully updated `ionos-core` end to end: `0.5.1 -> 0.5.2`, confirmed via `get_file_data`.
- `wp plugin update ionos-essentials` reproduced the documented Plugin_Upgrader bind-mount limitation exactly: downloaded from the correct resolved S3 URL, unpacked it, then failed at "Removing the old version of the plugin" because Docker won't let the container replace its own bind-mounted top-level directory - proving detection/resolution/download/unpack all work; only the final swap is blocked by this environment-specific constraint (also documented for wp-plugin packages, unlike wp-mu-plugin packages which copy in place).

Cleaned up fully afterward: `pnpm destroy`, removed `./mnt/update-test`, restored `stretch-extra-config.php` and `.env.local` to their original state (the isolated-stack env vars would otherwise have redirected the user's regular `pnpm start` to the throwaway container/ports, since `.env.local` is sourced into every script command).
