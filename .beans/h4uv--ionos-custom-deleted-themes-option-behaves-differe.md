---
# h4uv
title: IONOS_CUSTOM_DELETED_THEMES_OPTION behaves differently when absent vs an empty array
status: completed
type: bug
priority: low
created_at: 2026-08-20T12:04:42Z
updated_at: 2026-08-21T07:17:49Z
---

Found while refactoring the e2e hooks (bean z3o3). secondary-theme-dir.spec.js's 'deletable' test
deletes the provisioned `extendable` theme through the wp-admin UI and asserts its card disappears.
It passes when IONOS_CUSTOM_DELETED_THEMES_OPTION is absent and fails when the option exists holding
an empty array - the theme card stays on the page.

That difference should not exist: inc/secondary-theme-dir.php reads the option as
`\get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, [])` at every one of its ~8 call sites, so an
absent option and a stored `[]` should be indistinguishable.

## How it shows up

The two e2e entry points produce different baselines:

pnpm test:e2e -> option ABSENT -> 'deletable' passes
pnpm run test --use php --use e2e -> option is [] -> 'deletable' fails

(phpunit reinstalls WordPress into the same tables, and something during the rebuild writes the
empty array; the AFTER_START script does not touch this option.)

## What was ruled out

Bisected the spec's original beforeAll one line at a time against the combined path:

- `wp option delete IONOS_CUSTOM_DELETED_THEMES_OPTION` -> passes
- `wp --quiet option get siteurl` in its place -> fails

So it is the option value that matters, not a wp-cli bootstrap warming a cache or transient. The
other two lines of the original hook (`option set stretch_extra_extendable_theme_dir_initialized 1`,
`theme activate twentytwentyfive`) turned out to be unnecessary - both already hold those values in
both baselines.

Probing both baselines showed this option as the ONLY difference between them (theme list, theme
directories on disk, active theme, plugin list and the rest are identical).

## Current state

secondary-theme-dir.spec.js keeps a one-line beforeAll deleting the option, with a comment saying
it was established empirically and warning not to remove it without running
`pnpm run test --use php --use e2e` (an e2e-only run stays green either way).

## To investigate

Why the delete path (the `delete_theme` action handler around inc/secondary-theme-dir.php:100 and
the listing filter around line 67) behaves differently for `[]` than for a missing option. Suspects
worth checking: whether the stored value is really an array rather than an empty string or a
serialized `''`, and whether the autoload flag passed to `\update_option(..., true)` changes what
`get_option` returns for it. Once understood, either fix the product code or drop the hook line.

## Investigation round 1 - mechanism narrowed, root cause NOT found

Set back to todo: no code change shipped. The hook line in secondary-theme-dir.spec.js stays.

### Established (all reproduced 3/3, no phpunit needed)

Forcing the option to an empty array in a plain `pnpm test:e2e` run reproduces the failure, so the
combined `--use php --use e2e` path is no longer needed to work on this:

    wp --quiet option update IONOS_CUSTOM_DELETED_THEMES_OPTION '[]' --format=json   -> 2 failed, 3/3 runs
    wp option delete IONOS_CUSTOM_DELETED_THEMES_OPTION                              -> 3 passed, 3/3 runs

### The failure is latency, not a wrong option value

Instrumenting the real spec showed the actual error is not the `toHaveCount` mismatch originally
recorded - that is a downstream symptom. It is:

    Error: page.goto: net::ERR_ABORTED at http://localhost:8889/wp-admin/themes.php
    at await admin.visitAdminPage('/themes.php')   (the line right after the delete click)

and the test takes ~1 minute. So the delete navigation is still in flight when the test navigates
away, which aborts it; the assertion then polls a page rendered before the theme was gone.

An isolated probe that clicks delete, waits 500ms after opening the theme overlay and 4s after the
delete click, completes correctly in BOTH variants - card count 0, option written as
`a:1:{i:0;s:10:"extendable";}`. So the plugin's delete/hide logic works fine with `[]`; what changes
is how long the request takes.

### Ruled out

- `wp_prepare_themes_for_js`, `wp_get_themes` and the `delete_theme`/`switch_theme` handlers are
  no-ops for an empty list, and read the option as `get_option(..., [])` - absent and `[]` are
  indistinguishable to all of them (code read end to end).
- The rendered page is identical in both variants: same delete-theme link count, visibility, href
  and nonce; same single confirm dialog; same post-click URL.
- Blocked/slow outbound HTTP (the api.wordpress.org theme update check theory): the container
  reaches api.wordpress.org in 0.37s and `wp eval 'wp_update_themes()'` returns in 0.65s.
- Test flakiness: both variants are deterministic across 3 runs each.
- A plain `Promise.all([page.waitForEvent('load'), click])` does NOT fix it - the load event never
  arrives within its 10s default, which is itself further evidence of the latency.

### Next steps for whoever picks this up

1. Find where the time goes in the delete request when the option exists. Xdebug is configured in
   the image; alternatively bisect by timing `admin-ajax`/`themes.php?action=delete` server-side.
2. Beware of instrumentation artifacts: adding `page.waitForResponse` around the click made BOTH
   variants stop navigating at all, so measure server-side rather than from Playwright.
3. Whatever the cause, the test should also stop racing the delete navigation - the current
   `visitAdminPage` immediately after the click is what turns a slow delete into a hard failure.

## Resolved: it was never the option, it was a race on the admin-ajax delete

### What actually happens

Deleting a theme from the secondary directory does not navigate - WP's updates.js intercepts the
`a.delete-theme` link and does an `admin-ajax.php` POST (`action=delete-theme`). The spec clicked
the link and immediately called `admin.visitAdminPage('/themes.php')`, so it could render the next
page before the server had recorded anything. `toHaveCount` then retries only the locator, never
the navigation, so the assertion could never recover - hence the deterministic-looking failure.

### How the option misled the investigation

Proven not causal:

- rendered `themes.php?search=extendable` is byte-identical between "absent" and `[]`, apart from
  an unrelated `serverData._modified` timestamp (256838 bytes both, nonces normalized)
- driving the delete server-side with curl in a throwaway lab container: navigation path 0.575s vs
  0.599s, admin-ajax path 0.175s vs 0.173s - both write `a:1:{i:0;s:10:"extendable";}`, both arms
- instrumenting the real spec with the arm chosen by env var (so both arms run byte-identical spec
  code) showed the same client path in both: one `AJAX-POST action=delete-theme`, `AJAX-RESP 200
  {"success":true,...}`, same post-click URL. The only difference was the final count, 0 vs 1.
- adding `waitForTimeout(2000)` before the assertion made the `[]` arm pass, with identical server
  state in both arms: `option=array(0=>'extendable',) row=[a:1:{...},on] files=yes wpcli_lists=1`

So the option's presence only biased a razor-thin race - plausibly because `update_option` takes an
UPDATE path on an existing autoloaded row versus an INSERT via `add_option` when absent. A
sub-millisecond difference is enough to flip a race this tight every time, which is exactly why it
looked deterministic.

Also ruled out earlier: the plugin's filters/handlers (all no-ops for an empty list), blocked
outbound HTTP (api.wordpress.org answers in 0.37s, `wp_update_themes()` in 0.65s), and
`wp option delete` on a missing option failing (it warns and exits 0).

### Fix

- `secondary-theme-dir.spec.js` waits for the `action=delete-theme` admin-ajax response instead of
  racing it, and does the same for `action=install-theme` in 'installable', which had the same race
  hidden behind a fixed `waitForTimeout(1000)`.
- The `IONOS_CUSTOM_DELETED_THEMES_OPTION` hook is gone: the file now has no beforeAll at all, and
  the misleading comment claiming the line was load-bearing is gone with it.
- No product-code change - `inc/secondary-theme-dir.php` was correct throughout, so no changeset.

### Verification

- e2e-only: 3/3 runs green (option absent in that snapshot)
- combined `--use php --use e2e` filtered to this file: 2/2 green (option is `[]` there - the state
  that used to fail 3/3)
- full suites: combined -> phpunit OK (15 tests, 40 assertions) + e2e 28/28; e2e-only 28/28
- eslint clean
