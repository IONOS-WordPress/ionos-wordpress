---
# 0z2q
title: 'ionos-core: drop the GitHub update fallback, S3 only'
status: completed
type: task
priority: normal
created_at: 2026-09-24T08:32:32Z
updated_at: 2026-09-24T08:36:02Z
---

ionos-core is not yet published (not live), so unlike the other plugins covered by 4du2 there are no existing installations whose mu-plugin constants point at GitHub. The transition-period reasoning in 4du2 (keep GitHub as a fallback until S3 delivery is proven in production, because an installation stuck on the old GitHub-only constant can only heal via a future update) does not apply here - there is nothing to fall back for.

Remove the GitHub half of the S3/GitHub dual delivery path added in vjp2, ahead of and independent from 4du2's general "retire GitHub" effort, which stays gated on a production rollout.

## Todos

- [x] `packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php`: drop `LEGACY_INFO_JSON_URL` and the GitHub fallback branch in `fetch_update_info()`, keep only the S3 constant/lookup
- [x] Update/trim `update/tests/phpunit/UpdateTest.php` to drop fallback-precedence test cases that no longer apply
- [x] `docs/packages/wp-mu-plugin/test-mu-plugin`: checked - its GitHub fallback mirrors the ionos-essentials `Update URI` pattern, not ionos-core's hardcoded-constants pattern, and that pilot is a separate, already-published package. Left unchanged; updated `docs/7-release.md` and `docs/5-test.md` instead to stop describing ionos-core as S3-first/GitHub-fallback
- [x] Changeset skipped per user decision - unreleased/unpublished code, no changelog entry needed

## Notes

Scoped to ionos-core only. `packages/wp-plugin/ionos-essentials` keeps its GitHub fallback per 4du2, since that plugin is already live and its `Update URI` header may still point installations at github.com.

## Summary of Changes

- `packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php`: removed `LEGACY_INFO_JSON_URL` and the multi-source loop in `fetch_update_info()`; it now does a single S3 request and returns `null` on any failure (transport error, non-200/empty body, invalid JSON, missing `version`/`package`).
- `update/tests/phpunit/UpdateTest.php`: rewrote the 6 tests to cover the S3-only path (success + each failure mode returning `null`) instead of S3-then-GitHub precedence.
- `docs/7-release.md`: removed the `LEGACY_INFO_JSON_URL` mention from the mu-plugin copy-paste onboarding steps; scoped the 'S3-first, GitHub-fallback (transition period)' section to `wp-plugin` packages and added a note that `ionos-core` is S3-only since it was never published.
- `docs/5-test.md`: updated the self-update testing section to describe `ionos-essentials` as S3-first/GitHub-fallback and `ionos-core` as S3-only.
- Left `docs/packages/wp-mu-plugin/test-mu-plugin` untouched - its GitHub fallback follows the `ionos-essentials` `Update URI`-header pattern (a different, already-published pilot plugin), not ionos-core's hardcoded-constant pattern.
- No changeset: per user decision, since ionos-core has never been published there is nothing to record a changelog entry against.
- Verified: `pnpm test:php --php-opts "--filter UpdateTest --group ionos-core"` (6/6 passing) and `pnpm lint` (clean, pre-existing unrelated warnings only).
