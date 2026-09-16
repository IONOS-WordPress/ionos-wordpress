---
# ig4m
title: Serve plugin updates from S3 instead of GitHub releases
status: draft
type: epic
priority: high
created_at: 2026-09-16T12:39:15Z
updated_at: 2026-09-16T12:52:55Z
---

Move the plugin self-update mechanism away from GitHub releases towards the IONOS S3 object storage, keeping GitHub as a fallback during a transition period.

## Goal

- S3 ends up containing exactly the same files as the GitHub release `@ionos-wordpress/latest` (plus the versioned pre-release zips and the legacy `<plugin>.latest.zip` alias).
- Every released zip gets a matching `<plugin>-info.json` on S3, just like on GitHub.
- Two info.json flavours exist: the GitHub one points at GitHub release download URLs, the S3 one points at S3 URLs.
- Plugins query S3 first and fall back to GitHub only on error.
- During the test phase, artifacts are written to a configurable S3 folder (`test`) instead of `ionos-group`.

## Current state (as of 2026-09-16)

- `scripts/release.sh` uploads only the zips to `s3://web-hosting/ionos-group/`, never the `*-info.json` files.
- The S3 object name is rewritten from `<plugin>-latest-php7.4.zip` to `<plugin>.latest.zip`, which makes all PHP variants of one plugin collide on a single S3 key.
- `ionos-group` is a prefix inside the bucket `web-hosting`, not a bucket of its own.
- The bucket is publicly readable over HTTPS (`https://s3-de-central.profitbricks.com/web-hosting/ionos-group/<file>` returns 200).
- WordPress dispatches update checks via `update_plugins_<host-of-Update-URI>`, today `update_plugins_github.com`.

## Agreed design decisions

1. **Update URI** moves to the S3 host. Each plugin registers BOTH `update_plugins_github.com` and `update_plugins_s3-de-central.profitbricks.com` with the same S3-first resolver, so already installed copies keep updating.
2. **Configuration**: a single `S3_FOLDER` environment variable. `.env` keeps the production default `ionos-group`; the uncommitted `.env.local` sets `test` for the test phase. Bucket and endpoint stay hardcoded.
3. **S3 file names** are identical to the GitHub release asset names, and the legacy `<plugin>.latest.zip` alias is still written.
4. **Scope**: `ionos-essentials` (wp-plugin) and `ionos-core` (wp-mu-plugin) are the only packages that are actually released - every other wp-plugin/wp-mu-plugin package in the repo is `"private": true` and skipped by `scripts/pre-release.sh`. The `test-mu-plugin` pilot under `docs/packages` is updated too, because it is the documented copy-paste reference. `ionos-wpdev-caddy` is explicitly out of scope: it is private and therefore never released, so an update checker would have nothing to resolve against.
5. **Folder injection**: the S3 folder is baked into the plugin artifacts at build time via a `__S3_FOLDER__` placeholder, the same way `build.sh` already rewrites the `Requires PHP` header.
6. **Fallback semantics**: GitHub is queried only when S3 is unreachable, returns a non-200 status or invalid JSON. A valid S3 info.json always wins, no version comparison.
7. **Code sharing**: the existing copy-paste-per-package pattern is kept, as documented in `docs/7-release.md`.
8. **S3 content**: the versioned pre-release zips are mirrored to S3 as well, not only the `latest` ones.

## Where the test phase runs

Build time and release time must agree on `S3_FOLDER`, because the folder is baked into the plugin header during the pre-release workflow while the S3 upload happens in the release workflow. Two constraints make the main repository the wrong place to try this out:

- `pre-release.yml` is triggered by `push` to `main` and has no `workflow_dispatch`, so there is no per-run input to switch the folder.
- The zips uploaded to S3 are the same artifacts attached to the GitHub release `@ionos-wordpress/latest`. A test-phase release in the main repository would therefore ship plugins to real users whose `Update URI` points at the `test` folder.

**Decision: the test phase runs in a fork.** `docs/7-release.md` already recommends forking for work on the release scripts. In the fork, `.env.local` sets `S3_FOLDER=test`; pre-release and release are driven there, so build and upload see the same value by construction and the production `@ionos-wordpress/latest` release stays untouched. `.env` in the main repository keeps the `ionos-group` default and the workflows need no change.

Note that the `.env` entry must follow the repo's established override pattern (`S3_FOLDER="${S3_FOLDER:-ionos-group}"`, as used for `IMAGE_REGISTRY`), so an exported value wins and an empty one falls back to the production default.
