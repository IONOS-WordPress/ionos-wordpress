---
# 2nam
title: Remove the GitHub fallback from the plugin update resolvers
status: draft
type: task
priority: normal
created_at: 2026-09-17T12:03:00Z
updated_at: 2026-09-17T12:03:00Z
blocked_by:
    - ig4m
---

The GitHub fallback introduced with the S3 migration is a transition measure, not a permanent second source. Once fetching updates from S3 is considered stable, it has to come back out of every plugin, so an installation has exactly one place it looks for updates.

Leaving it in indefinitely is not neutral: it keeps a second delivery path alive that nobody exercises and therefore nobody notices breaking, it forces the release pipeline to keep publishing GitHub flavoured descriptors and assets, and it hides S3 outages instead of surfacing them.

## Scope

- [ ] `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php`: drop `LEGACY_INFO_JSON_URL` and reduce `fetch_update_info()` to the URL from the `Update URI` header
- [ ] Same plugin: drop the `update_plugins_github.com` hook registration, keeping only `update_plugins_s3-de-central.profitbricks.com`, and collapse the two-host loop back into a single `add_filter()` call
- [ ] `packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php`: drop the GitHub constant and its fallback branch
- [ ] `docs/packages/wp-mu-plugin/test-mu-plugin`: update the documented copy-paste reference so it stops teaching the fallback pattern
- [ ] Remove the transition-period wording from `docs/7-release.md`
- [ ] Changesets for the affected packages

## Entry condition

This is the risky half of the retirement, and the risk is one-directional.

Dropping the `update_plugins_github.com` registration cuts off every installation whose `Update URI` header still points at github.com, permanently. Such an installation cannot heal itself: the corrected header only arrives with an update it would no longer be offered. Recovering it would take a manual reinstall.

So do not start before S3 delivery has been observed working in production long enough that effectively no installation predating the switch is left. A concrete gate has to be agreed during refinement - a minimum number of release cycles, or better, telemetry on the share of installations still on a pre-S3 version.

Removing the fallback from `ionos-core` carries less risk, because a mu-plugin has no header and always reads whichever constants the installed version was built with. It could be split off and done earlier if that turns out to be useful.

## Relationship to the release pipeline

This bean covers the plugin side only. Stopping the pipeline from publishing GitHub descriptors and assets is [[retire-the-github-release-delivery-path-once-s3-delivery-is-proven]], which cannot happen before this one: as long as any installation may still fall back to GitHub, the files it falls back to have to keep existing.
