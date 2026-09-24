---
# 4du2
title: Retire the GitHub release delivery path once S3 delivery is proven
status: draft
type: task
priority: normal
created_at: 2026-09-16T12:56:05Z
updated_at: 2026-09-17T12:04:09Z
blocked_by:
  - ig4m
---

Follow-up to the epic "Serve plugin updates from S3 instead of GitHub releases". Once S3 delivery has been proven in production, the GitHub half of the dual delivery path becomes dead weight and should be removed, so there is only one way updates reach an installation.

The work splits into two phases with a strict order, because the files on GitHub may only disappear once nothing falls back to them any more.

## Phase 1 : remove the fallback from the plugins

The GitHub fallback is a transition measure, not a permanent second source. Leaving it in indefinitely is not neutral: it keeps a delivery path alive that nobody exercises and therefore nobody notices breaking, it forces the pipeline to keep publishing GitHub descriptors, and it masks S3 outages instead of surfacing them.

- [ ] `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php`: drop `LEGACY_INFO_JSON_URL` and reduce `fetch_update_info()` to the URL from the `Update URI` header
- [ ] Same plugin: drop the `update_plugins_github.com` hook registration and collapse the two-host loop back into a single `add_filter()` call
- [ ] `packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php`: drop the GitHub constant and its fallback branch. Lower risk than the wp-plugin, since a mu-plugin has no header and always reads whichever constants the installed version was built with - could be split off and done earlier
- [ ] `docs/packages/wp-mu-plugin/test-mu-plugin`: stop teaching the fallback pattern in the documented copy-paste reference
- [ ] Remove the transition-period wording from `docs/7-release.md`
- [ ] Changesets for the affected packages

## Phase 2 : stop publishing to GitHub

- [ ] `scripts/release.sh`: stop producing the GitHub flavoured `<plugin>-info.json` and stop attaching release assets to the floating `@ionos-wordpress/latest` release
- [ ] `scripts/release.sh`: drop the legacy `<plugin>.latest.zip` alias if nothing consumes it any more
- [ ] `docs/7-release.md`: describe the resulting single-source delivery and update the "publishing a new plugin or mu-plugin" checklist

## Entry condition

Phase 1 is the risky part, and the risk is one-directional. Dropping the `update_plugins_github.com` registration cuts off every installation whose `Update URI` header still points at github.com, permanently. Such an installation cannot heal itself: the corrected header only arrives with an update it would no longer be offered, so recovery means a manual reinstall.

So do not start before S3 delivery has been observed working in production long enough that effectively no installation predating the switch is left. A concrete gate has to be agreed during refinement - a minimum number of release cycles, or better, telemetry on the share of installations still on a pre-S3 version.

Phase 2 only needs phase 1 to be shipped and rolled out: once no plugin falls back to GitHub, the descriptors and assets there have no consumer left.

## Open question to resolve during refinement

The request behind this ticket mentions removing "the creation of the GitHub releases". It needs to be clarified how far that goes:

- **Narrow reading**: GitHub releases keep existing, they just no longer carry plugin assets or info.json files. The whole pre-release/release pipeline stays intact, since `scripts/pre-release.sh` and `scripts/release.sh` use the GitHub release objects and their `pre-release` flag as the pipeline's state machine (discover prereleases, sanity-check they share a commit, flip the flag once promoted). This is the low-risk reading.
- **Wide reading**: GitHub releases are abandoned entirely. That means replacing the pipeline's state machine and the changelog source, because `release.sh` reads the changelog for each info.json from the pre-release's release notes, and losing the releases also loses the human-readable release history and the git tags derived from it.

Clarify this before turning the ticket into work.
