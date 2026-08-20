---
# h4uv
title: IONOS_CUSTOM_DELETED_THEMES_OPTION behaves differently when absent vs an empty array
status: todo
type: bug
priority: low
created_at: 2026-08-20T12:04:42Z
updated_at: 2026-08-20T12:04:42Z
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
