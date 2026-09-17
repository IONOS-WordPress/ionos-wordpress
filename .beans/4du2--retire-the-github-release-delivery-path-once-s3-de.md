---
# 4du2
title: Retire the GitHub release delivery path once S3 delivery is proven
status: draft
type: task
priority: normal
created_at: 2026-09-16T12:56:05Z
updated_at: 2026-09-17T12:03:14Z
blocked_by:
    - ig4m
    - 2nam
---

Follow-up to the epic "Serve plugin updates from S3 instead of GitHub releases". Once S3 delivery has been proven in production, the GitHub half of the dual delivery path becomes dead weight and should be removed, so there is only one way updates reach an installation.

This bean covers the **release pipeline** side. Removing the fallback from the plugins themselves is [[remove-the-github-fallback-from-the-plugin-update-resolvers]] and has to happen first: as long as any installation may still fall back to GitHub, the files it falls back to have to keep existing.

## Scope

- [ ] `scripts/release.sh`: stop producing the GitHub flavoured `<plugin>-info.json` and stop attaching release assets to the floating `@ionos-wordpress/latest` release
- [ ] `scripts/release.sh`: drop the legacy `<plugin>.latest.zip` alias if nothing consumes it any more
- [ ] `docs/7-release.md`: describe the resulting single-source delivery and update the "publishing a new plugin or mu-plugin" checklist

## Entry condition

The plugin side has to be retired first. Once no shipped plugin falls back to GitHub any more, the descriptors and assets on GitHub have no consumer left and can stop being produced.

## Open question to resolve during refinement

The request behind this ticket mentions removing "the creation of the GitHub releases". It needs to be clarified how far that goes:

- **Narrow reading**: GitHub releases keep existing, they just no longer carry plugin assets or info.json files. The whole pre-release/release pipeline stays intact, since `scripts/pre-release.sh` and `scripts/release.sh` use the GitHub release objects and their `pre-release` flag as the pipeline's state machine (discover prereleases, sanity-check they share a commit, flip the flag once promoted). This is the low-risk reading.
- **Wide reading**: GitHub releases are abandoned entirely. That means replacing the pipeline's state machine and the changelog source, because `release.sh` reads the changelog for each info.json from the pre-release's release notes, and losing the releases also loses the human-readable release history and the git tags derived from it.

Clarify this before turning the ticket into work.
