---
# sca8
title: docker images are not rebuilt when their Dockerfile changes
status: completed
type: bug
created_at: 2026-08-07T07:02:16Z
updated_at: 2026-08-07T07:02:16Z
---

`ionos.wordpress.build_workspace_package_docker` skipped the build whenever a
`build-info` file existed and an image with the same (name, version) was present locally.
The image version only changes on release, so _any_ Dockerfile/entrypoint/scripts change was
silently ignored and `pnpm build` kept serving a stale image.

This masked the xdebug fix in [[x1pw]] : `pnpm destroy && pnpm start` recreated the container
correctly (`--add-host` present) but against the unchanged Aug-05 image, so the new
xdebug settings were never in it and breakpoints still did not work.

`scripts/start.sh` claimed in a comment that `pnpm build` "(re)builds the wp-alpine image
locally whenever its Dockerfile/entrypoint changed" - that was never true.

- [x] use `ionos.wordpress.is_workspace_package_up_to_date` (the mtime-based check added in zgcp) for docker packages too
- [x] keep the additional "image still exists locally" condition, so a pruned image forces a rebuild
- [x] fix the misleading comment in scripts/start.sh
- [x] verify: unchanged -> skip, touched Dockerfile -> rebuild

## Summary of Changes

The docker branch now runs the same content check every other package type uses, ANDed with
`docker image inspect`. Verified all three paths: first run rebuilds, second run logs
"skip building docker image ... : image already exists locally and is up to date",
and `touch Dockerfile` triggers a rebuild again.
