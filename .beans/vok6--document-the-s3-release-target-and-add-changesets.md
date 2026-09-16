---
# vok6
title: Document the S3 release target and add changesets
status: todo
type: task
priority: normal
created_at: 2026-09-16T12:40:17Z
updated_at: 2026-09-16T12:52:56Z
parent: ig4m
---

## Todos

- [ ] Extend `docs/7-release.md` with a section describing the S3 target: bucket, folder, the `S3_FOLDER` variable, the full file listing and the two info.json flavours
- [ ] Document the transition period (S3 first, GitHub fallback) and the condition under which the GitHub fallback can eventually be removed
- [ ] Update the "publishing a new plugin or mu-plugin" checklist in `docs/7-release.md`, which currently tells the reader to set the `Update URI` header to a GitHub URL
- [ ] Describe the test-phase workflow: run it in a fork with `S3_FOLDER=test` in the fork's uncommitted `.env.local`, and explain why it must not be run in the main repository (the S3 zips are the same artifacts attached to `@ionos-wordpress/latest`, and build and release have to agree on the folder value)
- [ ] Write changesets for the affected packages (ionos-essentials, ionos-core, ionos-wpdev-caddy) and present them for approval before writing
