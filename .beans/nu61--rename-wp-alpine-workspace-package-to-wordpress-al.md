---
# nu61
title: Rename wp-alpine workspace package to wordpress-alpine
status: completed
type: task
priority: normal
created_at: 2026-08-07T07:38:06Z
updated_at: 2026-08-07T07:41:08Z
---

Rename packages/docker/wp-alpine to packages/docker/wordpress-alpine, update the npm package name, docker image name/tags, CI workflow filename, and all references in scripts/docs/CI.

## Summary of Changes

- `git mv packages/docker/wp-alpine` -> `packages/docker/wordpress-alpine`
- `git mv .github/workflows/build-wp-alpine-image.yaml` -> `build-wordpress-alpine-image.yaml`
- package name `@ionos-wordpress/wp-alpine` -> `@ionos-wordpress/wordpress-alpine`
  (scripts/build.sh derives the docker image name from the package name, so the local
  image tag becomes `ionos-wordpress/wordpress-alpine:latest` automatically)
- `IMAGE_REPOSITORY` default `ionos-wordpress/wp-alpine-dev` -> `ionos-wordpress/wordpress-alpine-dev`
- shell var prefix `WP_ALPINE_*` -> `WORDPRESS_ALPINE_*`
- all remaining references updated in scripts/, docs/, README.md, .devcontainer/,
  .github/workflows/, .env*, .secrets.example, phpunit/bootstrap.php, playwright.config.js
- pnpm-lock.yaml regenerated

## Follow-up needed (operational, not code)

The published GHCR repository path changes from `ionos-wordpress/wp-alpine-dev` to
`ionos-wordpress/wordpress-alpine-dev`. Existing published images still live under the
old path, so the first CI run after this lands must republish before
`PHP_VERSION_OVERRIDE` pulls can succeed (scripts/test.sh falls back to a local build,
so it degrades gracefully). If a repo-level `IMAGE_REPOSITORY` Actions variable is set,
it must be updated too.
