# Ticket 1 — Core release-loop fix: support N concurrent prereleases

Status: TODO
Depends on: none
Parent plan: [../generalize-plugin-release-mechanism.md](../generalize-plugin-release-mechanism.md) (sections 1-2)
Jira: sub-task of [GPHWPP-4402](https://hosting-jira.1and1.org/browse/GPHWPP-4402)

## Goal

Make `scripts/release.sh` and `scripts/pre-release.sh` handle multiple concurrently-releasable
non-private packages correctly, instead of hard-assuming exactly one. These two files must land
together — fixing one without the other leaves a real data-loss bug (see scope below).

## Scope

- `scripts/release.sh`
  - Replace the `wc -l -ne 1` "expected exactly one prerelease" check with a loop over **all**
    `isPrerelease == true` releases (`gh release list --json name,isPrerelease --limit 1000`).
  - Add the same-commit sanity check (`git rev-list -n1 <tag>` per candidate) before processing;
    abort with a clear message if they don't all match.
  - Hoist "ensure `@ionos-wordpress/latest` exists" above the loop (runs once).
  - Wrap asset download/rename/upload, S3 upload, and `<plugin>-info.json` generation in the loop
    (already generic per-asset, just needs to run once per prerelease).
  - Move the "un-flag prerelease" step inside the loop.
  - Collect one bullet line per processed package; after the loop, do a single
    `gh release edit "@ionos-wordpress/latest"` with combined `--notes` and `--target HEAD`.
  - Update the Google Chat notification to list all packages promoted this run.
  - Graceful "nothing to release" exit (0) when no prereleases are found.
- `scripts/pre-release.sh`
  - Replace the blanket `gh release delete` of _every_ prerelease with a per-package-scoped
    delete (only stale prereleases whose name starts with `$PACKAGE_NAME@`), executed right
    before that package's `gh release create` call inside the existing per-package loop.
  - Update header comment to describe multi-package support.

## Acceptance criteria

- [ ] `release.sh` no longer errors when 2+ prereleases exist; it processes all of them.
- [ ] `release.sh` aborts with a clear message if discovered prereleases don't share one commit.
- [ ] `pre-release.sh` no longer deletes an unrelated package's still-pending prerelease when a
      different package gets a new prerelease cycle.
- [ ] `@ionos-wordpress/latest` ends up with correct `-latest-` assets and `-info.json` for every
      package promoted in a run, with combined release notes.
- [ ] Existing single-package (essentials-only) behavior is unchanged in output (same tags, same
      asset names, same S3 paths).
- [ ] Manual test per the parent plan's Verification steps 1-3 passes on a fork.

## Notes

- Optional/low-priority hardening (pnpm-metadata-based plugin-name lookup instead of filename
  regex, plan section 4) is **not** part of this ticket — only pick it up if a real plugin name
  is found to be at risk.
