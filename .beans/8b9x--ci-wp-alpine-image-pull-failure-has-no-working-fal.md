---
# 8b9x
title: 'CI: wp-alpine image pull failure has no working fallback'
status: completed
type: bug
priority: high
created_at: 2026-08-06T14:21:44Z
updated_at: 2026-08-06T14:30:05Z
---

The 'build and test' job of integration.yaml failed (run 31109430101) because the
throwaway test stack could not be started:

    docker: Error response from daemon: pull access denied for ionos-wordpress/wp-alpine

Root cause chain:

1. The 'install and pull prebuilt images' step pulls the prebuilt wp-alpine image
   from ghcr.io. In that run the pull aborted mid-transfer with a GHCR _secondary
   rate limit_ (HTTP 403 permission_denied) - transient, not a missing tag.
2. The step comment claims a failed pull "falls back to a local build". It does
   not: the following build step filters to './packages/wp-plugin/_',
   './packages/wp-mu-plugin/_' and '@ionos-wordpress/rector-php' only, explicitly
   excluding '@ionos-wordpress/wp-alpine'.
3. scripts/test.sh's default branch (no PHP_VERSION_OVERRIDE) just uses
   'ionos-wordpress/wp-alpine:latest' with neither retry nor local-build fallback
   (unlike the PHP_VERSION_OVERRIDE branch, which retries 5x/30s and then builds).

So a single flaky GHCR pull fails the whole workflow.

## Todos

- [x] retry the wp-alpine 'docker pull' in the pull step (mirror test.sh's 5x/30s loop)
- [x] let the build step detect a missing image itself (simpler than a step output that would have to be plumbed out of the devcontainer)
- [x] build '@ionos-wordpress/wp-alpine' in the build step when the pull did not succeed
- [x] fix the misleading comments that claim a fallback already exists

## Summary of Changes\n\n- `.github/workflows/integration.yaml`, pull step: the `docker pull` of the prebuilt wp-alpine image is now retried up to 3x with a 20s backoff. Retries are skipped when `docker manifest inspect` says the tag does not exist at all (a not-yet-published tag never appears by waiting); if that check is rate-limited too we fall through to the local build, which is the correct outcome anyway.\n- `.github/workflows/integration.yaml`, build step: builds `@ionos-wordpress/wp-alpine` when `ionos-wordpress/wp-alpine:latest` is absent, i.e. whenever the pull above did not succeed. This is the fallback the comments already claimed existed.\n- `scripts/test.sh`: the default (no `PHP_VERSION_OVERRIDE`) branch now builds the image if it is missing instead of letting the throwaway container die with a bare docker error. This also closes the same hole in the pre-release path, where `scripts/pre-release.sh` runs `pnpm test` without any prior build.\n\nNo changeset: CI/CD + internal tooling only (see docs/agent/changeset-workflow.md).\n\n## PHP-version consistency (done here too)\n\nThe three places that knew about php/alpine pairs disagreed: the CI pull hardcoded the `-php8.4` tag while `scripts/test.sh` rejects any `PHP_VERSION_OVERRIDE` other than `8.3` and built `ARG_PHP_VERSION=8.3`/`ARG_ALPINE_VERSION=3.20` locally, while the `8.3` leg of `build-wp-alpine-image.yaml`s matrix was commented out - so the min-version tag was never published and that override path could only ever build locally (after burning 5x30s of pointless pull retries first).\n\nFixed by making `packages/docker/wp-alpine/image-matrix.json` the single source of truth:\n\n- new `image-matrix.json` lists the published pairs (8.4/3.24 default, 8.3/3.20) with the default flagged.\n- `build-wp-alpine-image.yaml`: a preceding `matrix` job reads that file and hands the pairs to the build job via `fromJSON` (`strategy.matrix` cannot read a file itself). This re-enables the 8.3 leg. The job also fails loudly if `packages/docker/wp-alpine/.env` (which `scripts/build.sh` feeds to `docker build --build-arg` verbatim, so it cannot read JSON and has to repeat the default pair) drifts from the matrixs default entry, or if the file does not mark exactly one default.\n- `integration.yaml`: the pull tag suffix now comes from `.env`s `ARG_PHP_VERSION` instead of a hardcoded `-php8.4`, so the tag pulled and the image built by the fallback cannot disagree.\n- `scripts/test.sh`: `PHP_VERSION_OVERRIDE` is validated against the matrix file (error message now lists the actually-published variants) and the local fallback builds `ARG_PHP_VERSION`/`ARG_ALPINE_VERSION` from it instead of hardcoding 8.3/3.20.\n- `packages/docker/wp-alpine/Dockerfile`: header comment points at the matrix file instead of restating the pairs.
