---
# xla3
title: Bake playwright chromium into the devcontainer image
status: todo
type: task
priority: low
created_at: 2026-08-07T08:55:52Z
updated_at: 2026-08-07T08:55:52Z
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

- [ ] Add the install to `.devcontainer/Dockerfile` (after the existing `pnpx playwright install-deps`)
- [ ] Confirm the runtime `playwright install` is a no-op on a version match
- [ ] Simplify or remove the `PLAYWRIGHT_BROWSERS_PATH` handling in `scripts/test.sh`
- [ ] Measure the resulting devcontainer image size delta
