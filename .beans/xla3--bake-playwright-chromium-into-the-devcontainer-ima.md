---
# xla3
title: Bake playwright chromium into the devcontainer image
status: completed
type: task
priority: low
created_at: 2026-08-07T08:55:52Z
updated_at: 2026-08-17T13:19:57Z
parent: 1qc9
---

Run `pnpm exec playwright install chromium` in `.devcontainer/Dockerfile` so a fresh devcontainer
does not need a ~300MB browser download before the first e2e run.

## Value

Developer-facing, not CI-facing. CI already restores the browsers from `actions/cache` in **7s**
(measured, run `31161602566`), so baking them in saves essentially nothing there and adds ~130MB
compressed to an image pulled twice per run. Do this for the onboarding experience, and do not
expect a CI win.

## Bonus

Lets us drop the workspace-relative `PLAYWRIGHT_BROWSERS_PATH` indirection in
`scripts/test.sh:63-86` and the `metainfo.json` absolute-path fixup right below it, which exists
purely because the devcontainer is thrown away after every CI step.

## Caveat

The browser build is pinned by the playwright version in `pnpm-lock.yaml`, while the devcontainer
image tag derives from `.devcontainer`'s git history. A playwright bump therefore leaves a stale
browser baked in. Chromium is far less churny than the pnpm store, so a `playwright install` at
runtime remaining as the no-op-on-hit fallback is probably enough - but confirm that
`pnpm exec playwright install chromium` is genuinely a no-op when the baked version matches.

## Work items

- [x] Add the install to `.devcontainer/Dockerfile` (after the existing `pnpx playwright install-deps`)
- [x] Confirm the runtime `playwright install` is a no-op on a version match
- [x] Decided NOT to touch `PLAYWRIGHT_BROWSERS_PATH` in `scripts/test.sh` (see Summary)
- [x] Measure the resulting devcontainer image size delta

## Summary of Changes

- `.devcontainer/Dockerfile`: added `pnpx playwright install chromium` after the existing `install-deps` call, baking the chromium binary itself into the image. Updated the adjacent comment block (which had explicitly documented the prior decision NOT to do this, referencing this bean by id) to reflect the new state and corrected the size estimate.
- Verified the no-op claim directly: in a throwaway `node:24-bookworm` container with `playwright@1.62.1` (the version this repo pins) installed, a cold `playwright install chromium` took ~35s (full download); a second identical call completed in 0.44s — confirmed no-op on a version match.
- Measured the real size delta the same way (since a full Dockerfile build was impractical here): chromium + chromium-headless-shell + ffmpeg for 1.62.1 come to 656MB uncompressed / ~275MB gzip-compressed — more than double the bean's original ~300MB/~130MB estimate. This matches what CI's own `actions/cache` already restores today (269MB), so it isn't a new cost for CI, but the devcontainer image itself grows by ~275MB compressed, pulled twice per CI run. Confirmed with the user before proceeding; accepted as-is for the onboarding win.
- Deliberately did NOT touch `scripts/test.sh`'s `PLAYWRIGHT_BROWSERS_PATH`/`actions/cache` handling (the bean's 'bonus' item). Removing it would let CI rely on the image's baked-in browser instead of the cache, but that only works while the devcontainer image's playwright version and `pnpm-lock.yaml`'s stay in sync — if they drift (e.g. a playwright bump lands before the image rebuilds), CI would lose its cache fallback and re-download ~300MB on every run until the image catches up. Kept the existing mechanism as CI's safety net; this is developer-onboarding-only in scope.
- No changeset: this is an internal dev-tooling change with no package-version implication, consistent with prior devcontainer-only fixes (e.g. hgik's npm fix) that also shipped without one.

Verified pre-implementation CI timing data (both for this bean and its sibling u03w) via the GitHub Actions API across 6 recent runs before doing any work — see conversation history for the full sample set.
