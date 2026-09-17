---
# 4du2
title: Retire the GitHub release delivery path once S3 delivery is proven
status: draft
type: task
priority: normal
created_at: 2026-09-16T12:56:05Z
updated_at: 2026-09-16T12:56:05Z
blocked_by:
  - ig4m
---

Follow-up to the epic "Serve plugin updates from S3 instead of GitHub releases". Once S3 delivery has been proven in production, the GitHub half of the dual delivery path becomes dead weight and should be removed, so there is only one way updates reach an installation.

## Scope

- [ ] `scripts/release.sh`: stop producing the GitHub flavoured `<plugin>-info.json` and stop attaching release assets to the floating `@ionos-wordpress/latest` release
- [ ] `scripts/release.sh`: drop the legacy `<plugin>.latest.zip` alias if nothing consumes it any more
- [ ] `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php`: remove the GitHub fallback branch and the GitHub info.json constant; keep only the S3 resolver
- [ ] Same plugin: drop the `update_plugins_github.com` hook registration, keeping only `update_plugins_s3-de-central.profitbricks.com`
- [ ] `packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php`: remove the GitHub fallback
- [ ] `docs/packages/wp-mu-plugin/test-mu-plugin`: update the documented copy-paste reference accordingly
- [ ] `docs/7-release.md`: describe the resulting single-source delivery, remove the transition-period section and update the "publishing a new plugin or mu-plugin" checklist

## Entry condition

Do not start this before S3 delivery has been observed working in production for long enough that essentially no installation still carries a plugin version predating the S3 switch. As long as installations exist whose `Update URI` header points at github.com, dropping the `update_plugins_github.com` hook registration cuts them off from updates permanently - they cannot self-heal, because the corrected header only ships with an update they would no longer receive.

A concrete gate needs to be agreed during refinement, for example a minimum number of release cycles, or telemetry showing the share of installations still on a pre-S3 version.

## Open question to resolve during refinement

The request behind this ticket mentions removing "the creation of the GitHub releases". It needs to be clarified how far that goes:

- **Narrow reading**: GitHub releases keep existing, they just no longer carry plugin assets or info.json files. The whole pre-release/release pipeline stays intact, since `scripts/pre-release.sh` and `scripts/release.sh` use the GitHub release objects and their `pre-release` flag as the pipeline's state machine (discover prereleases, sanity-check they share a commit, flip the flag once promoted). This is the low-risk reading.
- **Wide reading**: GitHub releases are abandoned entirely. That means replacing the pipeline's state machine and the changelog source, because `release.sh` reads the changelog for each info.json from the pre-release's release notes, and losing the releases also loses the human-readable release history and the git tags derived from it.

Clarify this before turning the ticket into work.
