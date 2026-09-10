---
# okbi
title: Remove redundant DB-normalizing beforeAll/afterAll hooks from e2e specs
status: completed
type: task
priority: normal
created_at: 2026-08-20T10:16:41Z
updated_at: 2026-08-20T10:27:10Z
---

Follow-up to p3j2 (per-file DB snapshot/restore). Many *.spec.js files carry their own
beforeAll/afterAll hooks whose only purpose was to enter/leave the WordPress database in a
known state. Now that every spec file restores the snapshot as its outermost beforeAll, the
ones that merely re-assert the baseline are dead weight.

## Ground truth

`AFTER_START` is deliberately NOT wired for the ephemeral test container (scripts/test.sh passes
no --env AFTER_START), but the stretch-extra mu-plugin self-provisions on first load, so the
snapshot baseline is NOT a stock install. Probed it by running a throwaway spec against the
restored snapshot:

ionos_group_brand <ABSENT>
IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION ["plugins/01-ext-ion8dhas7-stretch/...","plugins/extendify/...","plugins/ionos-essentials/..."]
IONOS_CUSTOM_DELETED_PLUGINS_OPTION <ABSENT> (read as get_option(X, []) -> same as [])
IONOS_CUSTOM_DELETED_THEMES_OPTION <ABSENT> (read as get_option(X, []) -> same as [])
stretch_extra_extendable_theme_dir_initialized "1"
extendify_attempted_redirect_count "3"
ionos_nba_status / _nba_setup_completed / _loop_nba_actions_shown <ABSENT>
IONOS_SECURITY_FEATURE_OPTION all four flags true (== IONOS_SECURITY_FEATURE_OPTION_DEFAULT)
ionos_essentials_maintenance_mode <ABSENT>
wordpress_mcp_settings <ABSENT>
ionos_market / WPLANG <ABSENT>
template/stylesheet twentytwentyfive
user meta ionos_essentials_welcome <ABSENT>
user meta ionos_popup_after_timestamp <ABSENT>
user meta ionos_compromised_credentials_...v2 <ABSENT>
application passwords 0
active plugins ionos-essentials, 01-ext-ion8dhas7-stretch, extendify
wordpress-mcp not installed

So several hooks that look like resets are in fact deliberate deviations from the baseline and
must stay (e.g. ionos_group_brand=ionos, IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION=[], deleting
stretch_extra_extendable_theme_dir_initialized, the wpscan transient, ionos_market=de).

## Separate finding: the popup suppression never worked

Four specs set `ionos_popup_after_timestamp` to `${Math.MAX_SAFE_INTEGER}` - which is `undefined`
(the constant is Number.MAX_SAFE_INTEGER), so they wrote the literal string "undefined". It was
moot regardless: inc/dashboard/blocks/popup/index.php returns early when the meta is empty, and
it is absent at baseline, so the popup can never render. All four copies removed rather than
fixed.

## Decisions (asked)

- Duplicated preconditions (ionos_essentials_welcome=true, extendify_attempted_redirect_count=4)
  stay per-file rather than being hoisted into global-setup's snapshot, so the baseline keeps
  meaning "fresh container + global-setup" and each spec's deviations stay visible locally.
- mcp.spec.js keeps `wp plugin delete wordpress-mcp`: filesystem state, which the DB restore
  cannot undo, and it protects a retried run from the previous attempt's install.

## Todos

- [x] delete fully-redundant hooks (security, secondary-theme-dir beforeAll; secondary-plugin-dir, maintenance afterAll; welcome beforeAll)
- [x] trim dead lines from the remaining hooks (nba, security-options, tabs, mcp, marketplace, localization)
- [x] drop now-unused execTestCLI imports
- [x] full e2e suite green twice in a row

## Summary of Changes

5 hooks removed entirely:

- `security.spec.js` beforeAll, `welcome.spec.js` beforeAll, `secondary-theme-dir.spec.js` beforeAll
- `secondary-plugin-dir.spec.js` afterAll, `maintenance.spec.js` afterAll

7 hooks trimmed to only what actually deviates from the snapshot (marketplace, secondary-plugin-dir
beforeAll, next-best-actions, security-options, tabs, maintenance beforeAll, mcp), plus the inline
welcome-meta reset in dashboard-localization. `secondary-plugin-dir.spec.js`'s
RESET_IONOS_STRETCH_OPTIONS_CLI_COMMANDS const went away with its afterAll (single remaining use
inlined, along with its duplicated `wp option delete stretch_extra_extendable_theme_dir_initialized`).
Net -76/+53 lines across 11 files, most of the additions being comments recording why what remains
is not redundant.

Kept deliberately: ionos_group_brand=ionos (marketplace, myaccount), IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION=[]
and the theme-dir-initialized delete (marketplace, secondary-plugin-dir), the wpscan transient,
ionos_market=de (descriptify), welcome=true + extendify=4 in the 4 files that need them, the
intra-file brand revert in welcome.spec.js, and mcp's `wp plugin delete wordpress-mcp`.

## One hook was not what it looked like

`security.spec.js`'s beforeAll opened with `wp user update admin --user_pass="${WP_PASSWORD}"`, which
reads as re-asserting the password scripts/test.sh already sets. Removing it broke 'prevent log in
with e-mail': changing a password invalidates WordPress' auth cookie, so that line was really an
undeclared logout, and it is what made /wp-admin show the login form at all. Without it the shared
storage state keeps the context logged in, `#user_login` never exists and page.fill times out.
Refactored into an explicit `await page.context().clearCookies()` in the test itself, with a comment;
the existing requestUtils.setupRest() at the end of the test still restores the login state for the
tests that follow.

## Verification

- full e2e suite: 28/28 green, twice in a row (fullyParallel:false, workers:1, so file order is
  deterministic - cross-file leakage would show)
- secondary-theme-dir.spec.js run alone (the file whose entire beforeAll was deleted): 3/3, so it
  depends on the snapshot rather than on a preceding file
- eslint clean on all 11 files (no unused execTestCLI imports left), prettier clean

## Deferred

The popup block (inc/dashboard/blocks/popup/) has no e2e coverage at all. The four hooks that looked
like they suppressed it never did (Math.MAX_SAFE_INTEGER is undefined, and the block early-returns on
an empty meta anyway), so nothing is lost by removing them - but nothing tests the popup either.
