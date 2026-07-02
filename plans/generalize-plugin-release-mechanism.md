# Generalize release/pre-release mechanism to any non-private plugin/mu-plugin

Jira: [GPHWPP-4402](https://hosting-jira.1and1.org/browse/GPHWPP-4402)

## Context

Today only `@ionos-wordpress/essentials` (`packages/wp-plugin/ionos-essentials`) ever gets
published as a GitHub release. The goal is to let **any** non-private `wp-plugin` or
`wp-mu-plugin` package go through the same pre-release → release → S3 pipeline, without one
package's release blocking or corrupting another's.

Investigation of `scripts/pre-release.sh`, `scripts/release.sh`, the two GitHub workflows
(`.github/workflows/pre-release.yml`, `.github/workflows/release.yaml`), `scripts/build.sh`, and
the changeset config shows the system is **already mostly generic**:

- `pre-release.sh` already loops over _every_ `package.json` changed by `changeset version`,
  skips `"private": true` packages, and creates one GitHub release per package
  (tag `<package-name>@<version>`, flagged `--prerelease`). Multiple non-private packages
  changing in one release cycle already each get their own prerelease today.
- `build.sh` already builds/zips any `wp-plugin`/`wp-mu-plugin` workspace package generically
  (`<plugin-dir>-<version>-php<X.Y>.zip`), not just essentials.
- The S3 upload block in `release.sh` operates per-asset off the filename, so it already works
  for any plugin's asset name — it just never sees more than one plugin's assets today.

**The actual blocker is `scripts/release.sh`.** It hard-assumes **exactly one** GitHub release is
flagged `isPrerelease`:

```bash
if [[ -z "$PRE_RELEASE" || $(echo "$PRE_RELEASE" | wc -l) -ne 1 ]]; then
  error_message="skip releasing - expected exactly one release flagged as 'pre-release' ..."
```

As soon as a second non-private package is added and both change in the same release cycle,
`pre-release.sh` will correctly create two prerelease-flagged releases, but `release.sh` will
hard-fail with this exact error — this is "the tag can't be reused" problem described by the
user: it's not really about GitHub tags being single-use, it's that the _promotion_ step
(`release.sh`) can't cope with more than one prerelease existing at once.

Per user decisions during planning:

- Keep the **single shared** `@ionos-wordpress/latest` release/tag — every package's `-latest-`
  assets and `<plugin>-info.json` continue to live side-by-side in it as distinct filenames. No
  change to the `Update URI` header scheme, no migration needed.
- `wp-mu-plugin` packages are **download-only** — WordPress core has no update-checker mechanism
  for must-use plugins, so mu-plugin releases get the same GitHub/S3 artifact treatment but no
  self-update wiring (same as today's `stretch-extra`, which stays private for now).
- The self-hosted plugin update-checker PHP
  (`packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php`) stays a
  **copy-paste pattern** — not extracted into a shared package. New plugins that want
  self-update copy this file and adjust the hardcoded plugin folder / changelog path by hand.
  This just needs to be documented as the recognized pattern.

## Approach

### 1. `scripts/release.sh` — support N concurrent prereleases

Replace the single-`$PRE_RELEASE` logic with a loop, while preserving the existing safety net
(today's "exactly one" check exists specifically to catch stale/broken prereleases left over from
an aborted `pre-release.sh` run — see `docs/7-release.md` FAQ).

- Fetch **all** `isPrerelease == true` releases (`gh release list --json name,isPrerelease
--limit 1000` — add an explicit `--limit` since `gh release list` defaults to 30 and the repo
  will accumulate more releases over time).
- If none found: warn and exit 0 (nothing to release), same pattern `pre-release.sh` uses for "no
  changesets".
- **Sanity check (new, keeps today's safety net):** resolve the commit hash of each candidate
  prerelease tag (`git rev-list -n1 <tag>`). All of them must point at the _same_ commit — they
  were all produced by one `pre-release.sh` run. If they don't, abort with a clear error listing
  the offending releases and point at the existing manual-fixup instructions in
  `docs/7-release.md` (mirrors today's exactly-one-prerelease error, just re-scoped to
  "exactly one commit's worth of prereleases").
- Hoist the "ensure `$LATEST_RELEASE_TAG` release exists" block (currently lines ~57-72) above
  the loop — it only needs to run once.
- Wrap the per-asset work (asset download/rename/upload, S3 upload, `<plugin>-info.json`
  generation — currently lines ~91-166) in `for PRE_RELEASE in "${PRE_RELEASES[@]}"; do ... done`.
  This part is already written generically per-asset/per-plugin-name, so it needs no logic
  changes, just needs to run once per qualifying prerelease instead of once total.
- Move the "remove `pre-release` flag" step (currently line 169) inside the loop, so each
  processed release is individually flipped to non-prerelease.
- Collect one Markdown bullet line per processed package during the loop (package tag + link),
  and after the loop do a **single** `gh release edit "$LATEST_RELEASE_TAG"` call that sets
  `--target` (current `HEAD`, since all qualifying prereleases share one commit),
  `--latest=true`, `--prerelease=false`, `--draft=false`, and `--notes` built from the combined
  bullet list — replacing the current single-link `"latest release is [$PRE_RELEASE](...)"`.
- Update the Google Chat notification block at the end to report the full set of packages
  promoted in this run (reuse the same bullet list), instead of the single `$PRE_RELEASE` name.

### 2. `scripts/pre-release.sh` — scope the stale-prerelease cleanup per package

Already generic in how it builds releases, but review uncovered a real bug the multi-package
goal actually triggers: the existing cleanup step

```bash
# workaround : delete all existing prereleases (multiple releases can have the prerelease tag through canceled/broken releases)
gh release list --json name,isPrerelease | jq -r '.[] | select(.isPrerelease == true) | .name' | xargs -I {} gh release delete --yes {}
```

unconditionally deletes **every** prerelease-flagged release before creating this cycle's new
ones, regardless of which package they belong to. That's harmless with a single package
(essentials), but once two packages can release independently and asynchronously: pkgA gets a
prerelease in cycle 1 and isn't promoted yet; cycle 2 lands changesets for pkgB only;
`pre-release.sh` runs and wipes pkgA's still-pending, still-valid prerelease (and its attached
zip artifacts — `gh release delete` doesn't pass `--cleanup-tag`, so the git tag survives but the
release/artifacts are gone for good) before creating pkgB's release. pkgA's changes never reach
`@ionos-wordpress/latest` without a rebuild.

**Fix:** remove the blanket delete, and instead delete only the stale prerelease(s) for the
_specific package_ about to be (re)created, right before its `gh release create` call inside the
per-package loop:

```bash
# delete any stale prerelease(s) for THIS package only (handles canceled/broken previous runs
# for the same package) — leaves other packages' pending prereleases untouched
gh release list --json name,isPrerelease \
  | jq -r --arg pkg "$PACKAGE_NAME@" '.[] | select(.isPrerelease == true and (.name | startswith($pkg))) | .name' \
  | xargs -r -I {} gh release delete --yes {}
```

Also update the header comment block to state it supports releasing multiple non-private
packages in one cycle, each with independently-scoped stale-prerelease cleanup.

### 3. `.github/workflows/pre-release.yml` and `.github/workflows/release.yaml` — close the cross-workflow race

Each workflow's `concurrency: group: ${{ github.workflow }}-${{ github.ref }}` only guards
against overlapping runs of _itself_ — nothing stops `pre-release` (triggered by push to `main`)
and `release` (manual `workflow_dispatch`) from running at the same time. With the new loop in
`release.sh`, a run that starts while `pre-release.sh` is mid-way through its
delete-then-recreate sequence could read a partial (but same-commit) subset of prereleases and
silently promote only some packages, leaving the rest stuck as prerelease until manually re-run.
Previously this race would very likely trip the old "exactly one" check and abort loudly; the new
loop removes that accidental safety net.

**Fix:** give both jobs the same literal, shared concurrency group so GitHub serializes them
against each other instead of relying on `github.workflow` (which differs per file):

```yaml
concurrency:
  group: ionos-wordpress-release-pipeline
  cancel-in-progress: false
```

Apply this to the `pre-release` job in `pre-release.yml` and the `release` job in
`release.yaml`, replacing their current per-workflow group. `cancel-in-progress: false` (the
default) makes the later trigger queue rather than cancel the earlier one — both need to
complete, not clobber each other.

### 4. `scripts/release.sh` — harden plugin-name derivation (optional, low priority)

`PLUGIN=$(echo $ASSET | sed -E 's/^(.*)-[0-9]+\.[0-9]+\.[0-9]+.*/\1/')` reverse-engineers the
plugin folder name from the built zip filename. This was never stress-tested against varied
plugin-name shapes because there was only ever one plugin. Not a blocker, but if a new plugin
name could plausibly confuse this regex (e.g. contains a version-like substring), replace it with
a lookup against pnpm's own workspace metadata instead of guessing from the filename:

```bash
PACKAGE_DIR_NAME=$(pnpm ls --filter "$PACKAGE_NAME" --json --depth -1 | jq -r '.[0].path' | xargs basename)
```

Treat this as optional hardening to do only if/when a real plugin name at risk shows up — not
required for the initial generalization.

### 5. Documentation updates

- `docs/7-release.md`:
  - Remove/rewrite the caveat "The release process assumes that there exists **exactly one**
    release flagged `pre release`" — replace with an explanation of the new multi-package
    behavior and the same-commit sanity check / how to resolve it if triggered.
  - Add a short **"Publishing a new plugin or mu-plugin"** checklist:
    - set `"private": false` in the package's `package.json` (this is the only switch
      `pre-release.sh` checks),
    - a changeset targeting that package is required to trigger a version bump/release
      (existing changeset workflow, unchanged),
    - build/zip/S3 upload work automatically — no per-plugin script changes needed,
    - **wp-mu-plugin packages are download-only**: call out explicitly that WordPress has no
      update-checker mechanism for must-use plugins, so mu-plugin releases will never get a
      `Update URI`-driven in-dashboard update — they're published as downloadable/installable
      artifacts only,
    - for `wp-plugin` packages that want in-dashboard self-update, copy
      `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php` into the new
      plugin and adjust the hardcoded plugin folder name / changelog raw-URL path for the new
      plugin (documented as the recognized copy-paste pattern, per decision above), plus set the
      plugin's `Update URI` header to
      `https://github.com/<org>/<repo>/releases/download/%40ionos-wordpress%2Flatest/<plugin>-info.json`.
  - Update the explanation of what `@ionos-wordpress/latest` contains: assets/info.json files
    from _every_ published package accumulate there over time, keyed by filename; a release cycle
    only touches the assets for packages that had a fresh prerelease in that cycle.
- Update the header comment in `scripts/release.sh` (currently describes a single
  pre-release → latest handoff) to describe the multi-package loop.

### 6. `packages/wp-mu-plugin/test-mu-plugin` — mu-plugin self-update pilot

New feature, layered on top of sections 1-5: give `test-mu-plugin` (the monorepo's existing
mu-plugin test fixture — already `"private"` is unset in its `package.json`, i.e. already
non-private under `pre-release.sh`'s `.private // false` check, so it's already positioned to
flow through the generalized release pipeline once it is) working self-update capability,
**without** walking back the earlier "mu-plugins are download-only" decision for mu-plugins in
general — `stretch-extra` and future mu-plugins stay download-only by default. `test-mu-plugin`
becomes a deliberate, small, self-contained pilot for what a mu-plugin self-update mechanism can
look like, since WordPress core provides no equivalent for mu-plugins out of the box.

**Why the essentials pattern (`update_plugins_github.com` filter) can't be reused as-is:** that
filter is invoked from inside `wp_update_plugins()` while iterating `get_plugins()` — mu-plugins
are never part of that list, so the filter simply never fires for them. A different, lower-level
approach is needed.

**Design — reuse WordPress core primitives, keep custom code to the update logic itself:**

- **Trigger — piggyback on WP's own existing cron hook, add no new scheduling code.** WordPress
  core already schedules a recurring `wp_update_plugins` cron event (twice daily, the same cadence
  `docs/7-release.md` already documents for `wp_update_plugins()`). That event is a normal action
  hook — any code can attach to it with `add_action('wp_update_plugins', ...)`. Since mu-plugins
  have no activation hook to bootstrap a custom `wp_schedule_event()` call from (mu-plugins are
  never "activated"), hooking the action WP already fires is simpler _and_ avoids writing/testing
  our own scheduling code entirely.
- **Version source — reuse the exact `<plugin>-info.json` convention `release.sh` already
  produces for every non-private package** (section 1-5 work makes this automatic for
  `test-mu-plugin` too, no extra release-script change needed). Fetch it with `wp_remote_get()`,
  same as `inc/update/index.php` does for essentials.
- **Locate the info.json URL — reuse `get_plugin_data()`'s generic header parser.** Add an
  `Update URI` header to `test-mu-plugin.php` (same convention as essentials/wpdev-caddy) purely
  as the one source of truth for the URL; read it back with
  `\get_plugin_data(PLUGIN_FILE)['UpdateURI']` — `get_plugin_data()` just parses file headers, it
  works identically regardless of plugin type.
- **Compare versions** with `\get_plugin_data(PLUGIN_FILE)['Version']` vs. the info.json's
  `version`, using PHP's `version_compare()`.
- **Download + install — reuse the same low-level file/upgrade primitives `Plugin_Upgrader` is
  built on, without pulling in `Plugin_Upgrader` itself** (it assumes a `wp-content/plugins/<slug>/`
  layout and fires activate/deactivate hooks that don't apply to mu-plugins):
  - `require_once ABSPATH . 'wp-admin/includes/file.php';` for `download_url()`, `unzip_file()`,
    `WP_Filesystem()`.
  - `download_url($info_json['package'])` → temp zip file (built-in error handling).
  - `WP_Filesystem()` + `unzip_file($tmp_zip, $tmp_dir)` → extract to a temp directory.
  - Since the mu-plugin's on-disk shape is exactly one top-level file (`test-mu-plugin.php`) plus
    one same-named companion folder (`test-mu-plugin/`), swap both in with two
    `$wp_filesystem->move($from, $to, true)` calls (overwrite) instead of a generic recursive
    `copy_dir()` — on the common 'direct' filesystem method this is a `rename()` per item, which
    is both less code and far closer to atomic than a multi-file recursive copy.
  - **Stability safety net (still small):** rename the _current_ file/folder to a `.bak` suffix
    before moving the new ones in; only delete the backup after confirming
    `get_plugin_data(PLUGIN_FILE)['Version']` now reads the expected new version; restore from
    `.bak` automatically if it doesn't. This bounds the worst case (a bad build breaking the
    mu-plugin, which — unlike a regular plugin — can't be "deactivated" from wp-admin) to an
    auto-detected, auto-reverted failure instead of a silently broken site.
  - Clean up the temp zip/dir either way.
- **Observability, kept minimal:** store the last check's outcome (timestamp, previous/new
  version, success/failure) in a single `update_option('test_mu_plugin_last_update_check', [...])`
  call — enough to debug without building an admin UI.

**Known limitation to document, not solve in v1:** `WP_Filesystem()` may fall back to asking for
FTP/SSH credentials on hosts where the PHP process doesn't own the files directly, which has no
UI to answer in a cron context — on such hosts the update silently no-ops and logs an error,
requiring a manual update. Acceptable for a pilot on a test fixture; worth a callout if this
pattern is ever promoted beyond `test-mu-plugin`.

**Sequencing:** this depends on sections 1-2 landing first — `test-mu-plugin`'s `-info.json` only
gets produced/kept correctly once `release.sh`/`pre-release.sh` can handle it as a second
concurrently-releasable package alongside essentials.

**Files to add:**

- `packages/wp-mu-plugin/test-mu-plugin/test-mu-plugin/inc/update/index.php` — the mechanism
  described above, `require_once`'d from `test-mu-plugin.php` (mirrors how essentials wires up
  `inc/update/index.php`).
- `packages/wp-mu-plugin/test-mu-plugin/test-mu-plugin.php` — add the `Update URI` header.

### Out of scope (per explicit user decisions)

- No per-package `@latest` tags — staying with the single shared `@ionos-wordpress/latest`.
- No self-update mechanism for mu-plugins **in general** — `stretch-extra` and future mu-plugins
  stay download-only by default. `test-mu-plugin` (section 6) is a deliberate, scoped pilot
  exception, not a change to the default policy.
- No extraction of `inc/update/index.php` into a shared/parameterized package.

## Files to change

- `scripts/release.sh` — core loop restructuring described above (plus optional plugin-name
  derivation hardening).
- `scripts/pre-release.sh` — scope the stale-prerelease cleanup to the package being released
  (not just a header comment — see fix above), plus the header comment update.
- `.github/workflows/pre-release.yml` and `.github/workflows/release.yaml` — shared concurrency
  group to close the cross-workflow race.
- `docs/7-release.md` — caveat rewrite + new "publishing a new plugin" checklist.
- `packages/wp-mu-plugin/test-mu-plugin/test-mu-plugin/inc/update/index.php` (new) and
  `packages/wp-mu-plugin/test-mu-plugin/test-mu-plugin.php` — mu-plugin self-update pilot
  (section 6).

## Verification

Since this touches real GitHub Releases/S3 via `gh`/`aws` CLI, verify without touching the real
`IONOS-WordPress/ionos-wordpress` repo:

1. Shellcheck / `bash -n` the modified `release.sh` and `pre-release.sh` for syntax correctness.
2. On a personal fork (per the existing "local usage" instructions in both scripts' header
   comments and `docs/7-release.md`), temporarily flip a second package (e.g.
   `packages/wp-plugin/ionos-wpdev-caddy`) to `"private": false`, add a changeset touching both
   it and essentials, and run the full local flow:
   - `pnpm pre-release` → confirm **two** GitHub releases are created, both flagged prerelease,
     tagged `<pkg>@<version>`, each with correct artifacts attached.
   - `./scripts/release.sh` → confirm:
     - both prereleases are found and processed (no "expected exactly one" error),
     - the shared `@ionos-wordpress/latest` release ends up with fresh `-latest-` assets and
       `-info.json` for _both_ packages,
     - both source prereleases are unflagged and now show as the `latest`-titled originals,
     - the `@ionos-wordpress/latest` release notes list both promoted packages,
     - S3 (`s3://web-hosting/ionos-group/`) received both packages' `.latest.zip` uploads.
   - Re-run `./scripts/release.sh` with no pending prerelease → confirm graceful "nothing to
     release" exit, not an error.
   - Temporarily leave two _stale_ prereleases at different commits (simulate a broken run) →
     confirm the new same-commit sanity check aborts with a clear message instead of silently
     mis-promoting them.
   - Revert the temporary `private: false` flip and test changeset before finishing.
3. Verify the scoped cleanup fix in `pre-release.sh`: create a prerelease for pkgA only, do
   **not** promote it, then run a second `pre-release.sh` cycle with a changeset for pkgB only →
   confirm pkgA's prerelease still exists afterward (only a stale prerelease matching pkgB's own
   name, if any, would be deleted).
4. Verify the concurrency fix: confirm both `pre-release.yml` and `release.yaml` show the same
   `ionos-wordpress-release-pipeline` group in the Actions UI, and that triggering one while the
   other is running queues rather than runs concurrently or cancels the other.
5. Verify the `test-mu-plugin` self-update pilot against a local `wp-env` instance:
   - Publish an initial version through the (by-then generalized) pipeline, install it, confirm
     `get_plugin_data()` reports the installed version.
   - Publish a bumped version, manually fire the `wp_update_plugins` cron hook (`wp cron event run
wp_update_plugins` via WP-CLI, or trigger `wp-cron.php` directly) and confirm the mu-plugin's
     files are replaced and the new version is live.
   - Simulate a broken build (e.g. corrupt the published zip or introduce a fatal-error version)
     and confirm the `.bak` restore path kicks in and the site keeps working on the old version
     instead of fataling.
   - Confirm `test_mu_plugin_last_update_check` reflects each check's outcome.
