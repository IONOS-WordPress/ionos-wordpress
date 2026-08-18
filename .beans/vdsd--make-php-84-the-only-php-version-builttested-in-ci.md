---
# vdsd
title: Make PHP 8.4 the only PHP version built/tested in CI
status: completed
type: task
priority: normal
created_at: 2026-08-18T10:02:46Z
updated_at: 2026-08-18T10:08:31Z
---

Drop PHP 8.3 as a second CI-built/tested variant. PHP 8.4 becomes the sole
default and only version the `wordpress-alpine` image matrix builds and the
test suite runs against.

## Why

`AGENTS.md` currently states PHP 8.3+ as the minimum supported version, and
`packages/docker/wordpress-alpine/image-matrix.json` publishes both an 8.4
(default) and an 8.3 image so `PHP_VERSION_OVERRIDE=8.3` can run the full
PHPUnit suite against the stated floor.

That floor check is redundant: `scripts/test.sh`'s rector step already lints
every distributable plugin's transpiled `dist/*-php7.4` output with `php -l`
against `php:7.4-cli` — a lower version than 8.3. If the code is expected to
run on a minimum PHP version at all, rector's 7.4 target is the real floor,
not 8.3. Keeping the 8.3 image/matrix entry buys no additional compatibility
signal, just double the image builds and CI time.

**Caveat:** the rector step is a syntax lint only (`php -l`), not a test
execution — it never runs PHPUnit against PHP 7.4. Dropping the 8.3 image
means CI no longer _executes_ code on anything but 8.4; a runtime/behavioral
incompatibility with PHP 7.4 (e.g. a removed function used at runtime, not
just a syntax construct) would only be caught by the lint if it manifests as
a parse error, not by actually running the code. Accepting this gap is the
premise of this ticket, not a blind spot to fix here.

## Scope

- [x] `packages/docker/wordpress-alpine/image-matrix.json`: removed the
      `8.3` entry, kept `8.4` as `"default": true`
- [x] Kept `PHP_VERSION_OVERRIDE` and the matrix-driven CI job in
      `.github/workflows/build-wordpress-alpine-image.yaml` as-is (single-entry
      matrix still works) so a future PHP version can be added back the same
      way
- [x] `AGENTS.md`: changed "PHP 8.3+" (both occurrences) to "PHP 8.4"
- [x] `docs/agent/php-standards.md`: changed "PHP 8.3+" to "PHP 8.4"
- [x] Do **not** touch completed beans or
      `docs/agent/wp-env-to-alpine-migration-plan.md` — they're historical
      record of the decision made at the time; only living guidance changed
- [x] Also fixed stale comments/hardcoded tags that directly referenced the
      old 8.3 minimum and would otherwise have drifted from the new policy:
      `scripts/build.sh` (comment + hardcoded `wordpress:cli-php8.3` →
      `wordpress:cli-php8.4`), `scripts/test.sh` (PHP_VERSION_OVERRIDE
      comment block), `packages/docker/wordpress-alpine/Dockerfile` (matrix
      comment), `.github/workflows/build-wordpress-alpine-image.yaml` (header
      comment). Left `scripts/stretch-extra.sh`'s `php:8.3-cli` alone - its
      own comment states that pin is arbitrary, not a compatibility
      requirement.

## Out of scope

- Removing/collapsing `PHP_VERSION_OVERRIDE` machinery itself
- Editing completed/historical beans (gjbp, 7wdg, zr06, e6mc, etc.) or the
  migration-plan doc
- Making the rector lint step execute PHPUnit against 7.4 (noted as a caveat
  above, not addressed here)

## Summary of Changes

Trimmed `image-matrix.json` to the single PHP 8.4 entry, so CI now builds and publishes only one `wordpress-alpine` image instead of two. Updated `AGENTS.md` and `docs/agent/php-standards.md` to state "PHP 8.4" instead of "PHP 8.3+". Also swept for and fixed other places that hardcoded or documented the old 8.3-minimum policy so nothing was left drifted: `scripts/build.sh`'s dockerized wp-cli image tag and comment, `scripts/test.sh`'s PHP_VERSION_OVERRIDE comment block, the Dockerfile's matrix comment, and the build workflow's header comment. Historical/completed beans and `docs/agent/wp-env-to-alpine-migration-plan.md` were deliberately left untouched as historical record.
