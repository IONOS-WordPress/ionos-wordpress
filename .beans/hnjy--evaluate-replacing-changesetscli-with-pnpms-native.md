---
# hnjy
title: Evaluate replacing @changesets/cli with pnpm's native changeset workflow
status: completed
type: task
priority: normal
created_at: 2026-08-17T10:25:15Z
updated_at: 2026-08-17T11:58:33Z
parent: 3pr5
---

Research whether pnpm's built-in changeset/publish support covers this repo's current bump-type/package-name/multi-package needs (currently handled via @changesets/cli, invoked through scripts/changeset.sh — see package.json:15,67). Outcome may be 'keep changesets' — this is an evaluation, not a guaranteed migration. If pnpm's native support is sufficient, drop the external dependency.

## Findings

pnpm has grown native workspace release management very recently (pnpm 11.11-11.14, per
https://pnpm.io/versioning): `pnpm change` (records an intent, writes `.changeset/*.md` in the same
format @changesets/cli uses), `pnpm change status` (preview), and `pnpm version -r` (consumes pending
intents, bumps versions, propagates to dependents, writes changelogs). Configuration lives under a
new `versioning` key in `pnpm-workspace.yaml` (`fixed`, `ignore`, `maxBump`, `lanes`, `epics`,
`changelog.storage`).

Tested directly against this repo's actual `.changeset/*.md` files under the already-upgraded pnpm
11.22.0, rather than evaluating from documentation alone. Three blockers surfaced:

1. **Version computation looks broken/incomplete for this repo's packages.** `pnpm change status`
   and `pnpm version -r --dry-run` both reported `@ionos-wordpress/essentials: 1.6.1 -> 1.6.1 (patch,
via intent)` and `ionos-wordpress: 1.0.3 -> 1.0.3 (major, via intent)` - the "new" version is
   identical to the current one in both cases, despite a real patch and a real major intent existing
   (`.changeset/fix-buttons-style-doing-it-wrong.md`, `.changeset/upgrade-pnpm-to-11.md`). A patch on
   1.6.1 should read 1.6.2; a major on 1.0.3 should read 2.0.0. This reproduces regardless of the
   `private` flag (root is `private: true`, essentials isn't), so that's not the cause. This alone
   would make the feature unsafe to rely on for actual releases right now.
2. **No machine-readable status output.** `pnpm change status --json` -> `[ERROR] Unknown option:
'json'`. `scripts/pre-release.sh` depends heavily on `changeset status --output json`'s
   `.changesets` (count) and `.releases[].name/.oldVersion/.newVersion` (used both to decide whether
   there's anything to release and to build the Google Chat announcement message). No native
   equivalent exists.
3. **`pnpm version -r` never creates git commits or tags**, by design and unconditionally - the
   `--no-git-tag-version` flag's own help text says "Git commits and tags are always skipped in
   recursive mode." The current pipeline depends on per-package git tags (`pnpm changeset tag`,
   `$PACKAGE_NAME@$PACKAGE_VERSION`) to drive `pnpm gh release create` for each non-private,
   distributable package. There's no flag to opt back into this.

Additionally, default changelog storage is `registry` (no committed `CHANGELOG.md`; composed at
publish time) - this repo relies on committed per-package `CHANGELOG.md` files, diffed directly to
build GitHub release notes (`scripts/pre-release.sh:147`). `versioning.changelog.storage:
repository` would restore that, but doesn't fix blockers 1-3.

## Recommendation: keep @changesets/cli

Not a migration right now. Reasons: a reproducible version-computation bug/gap in the exact
release-plan step this repo would depend on, a missing JSON output the release script structurally
needs, and a hard architectural gap (no git tagging in recursive mode) that the current per-package
GitHub-release workflow cannot work around. The feature is also very new (a few months old as of
this evaluation) for something as consequential as the release pipeline. `@changesets/cli` stays.

Worth revisiting later if pnpm's native versioning matures (JSON status output, fixed version
computation, and either recursive tagging support or a documented reason it's intentionally
unsupported that this repo's workflow could adapt to).
