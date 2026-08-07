---
# tp6d
title: pnpm start does not generate .vscode/launch.json
status: completed
type: bug
created_at: 2026-08-06T14:37:29Z
updated_at: 2026-08-06T14:37:29Z
---

The wp-env era generated `.vscode/launch.json` (xdebug pathMappings) on every
`pnpm start` via `scripts/wp-env-after-start.sh`. After the wp-alpine migration the
port of that logic (`packages/docker/wp-alpine/scripts/generate-vscode-launch.sh`)
was never wired into any pnpm command, so PHP debugging in VS Code was broken.

- [x] wire the generator into `scripts/start.sh`
- [x] make the generator self-bootstrapping (sources `bootstrap.sh` + `_docker-mounts.sh`)
- [x] fix the `/htdocs` mapping to point at the version-keyed core dir `mnt/wordpress-core/<VERSION_DIR>`
- [x] only emit the nested mu-plugin dir mapping when that dir exists (mirrors _docker-mounts.sh)
- [x] re-add the phpunit mappings (`/wordpress-phpunit`, `/htdocs/phpunit`)
- [x] make the script executable

## Summary of Changes

`scripts/start.sh` now calls the generator before the readiness wait, so
`.vscode/launch.json` is regenerated on every `pnpm start` and always matches the
packages currently bind-mounted into the container.
