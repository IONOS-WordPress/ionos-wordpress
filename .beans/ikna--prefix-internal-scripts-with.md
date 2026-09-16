---
# ikna
title: prefix internal scripts with _
status: completed
type: task
priority: normal
created_at: 2026-08-07T08:31:07Z
updated_at: 2026-08-07T08:32:20Z
---

Scripts that are only invoked by other scripts / CI workflows (never by a pnpm script
target) must follow the `_` prefix convention already documented in scripts/README.md.

Renames:

- [x] .github/shared/scripts/docker-subproject-image-tag.sh -> _docker-subproject-image-tag.sh
- [x] .github/shared/scripts/docker-subproject-image-pull.sh -> _docker-subproject-image-pull.sh
- [x] .github/shared/scripts/docker-subproject-image-push.sh -> _docker-subproject-image-push.sh
- [x] packages/docker/wordpress-alpine/scripts/generate-vscode-launch.sh -> _generate-vscode-launch.sh
- [x] scripts/includes/bootstrap.sh -> _bootstrap.sh
- [x] scripts/includes/update-dependencies.sh -> _update-dependencies.sh
- [x] update all callers (scripts, workflows, composite actions, git hooks)
- [x] update prose references (README.md, docs/, .gitignore comments)

Out of scope (intentionally not renamed):

- packages/docker/wordpress-alpine/docker-entrypoint.sh (docker convention, Dockerfile)
- packages/docker/wordpress-alpine/examples/after-start-ionos-wordpress.sh (user-facing example)
- packages/*/scripts/update-dependencies.sh + postpack.sh (they ARE pnpm targets)
- scripts/_prepare.sh (pnpm lifecycle hook, already prefixed)
- historical .beans/*.md records

## Summary of Changes\n\n6 scripts renamed with `git mv` (history preserved), 46 files rewritten to the new paths.\n\n- `.github/shared/scripts/_docker-subproject-image-{tag,pull,push}.sh` - callers: `integration.yaml`, `build-wordpress-alpine-image.yaml`, the `docker-subproject-image-name` / `devcontainer-image-name` composite actions, `scripts/test.sh`, `.gitignore` comment.\n- `packages/docker/wordpress-alpine/scripts/_generate-vscode-launch.sh` - callers: `scripts/start.sh`, its own generated-file banner, root `README.md`.\n- `scripts/includes/_bootstrap.sh` - sourced by all 24 `scripts/*.sh` plus `.githooks/pre-commit`, `.githooks/pre-push`, `_generate-vscode-launch.sh` and `_update-dependencies.sh`. `includes/` is now uniformly `_`-prefixed (joins `_docker-mounts.sh`, `_vscode.sh`).\n- `scripts/includes/_update-dependencies.sh` - sourced by the 5 `packages/docker/*/scripts/update-dependencies.sh`.\n\nAlso updated: `docs/agent/wp-env-to-alpine-migration-plan.md`. `CHANGELOG.md` and `.beans/*.md` left untouched as historical records.\n\nVerified: `pnpm beans list`, `_docker-subproject-image-tag.sh`, `_generate-vscode-launch.sh` all run; `bash -n` clean on every script except the 5 that already failed at HEAD (`build.sh`, `lint.sh`, `stretch-extra.sh`, `test.sh`, `update-dependencies.sh` - pre-existing, unrelated).
