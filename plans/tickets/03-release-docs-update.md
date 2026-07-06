# Ticket 3 — Update release documentation for multi-package support

Status: DONE
Depends on: Ticket 1 (describes its behavior)
Parent plan: [../generalize-plugin-release-mechanism.md](../generalize-plugin-release-mechanism.md) (section 5)
Jira: sub-task of [GPHWPP-4402](https://hosting-jira.1and1.org/browse/GPHWPP-4402)

## Goal

Bring `docs/7-release.md` (and the script header comments) up to date with the new multi-package
behavior, and give future plugin/mu-plugin authors a clear checklist for onboarding.

## Scope

- `docs/7-release.md`
  - Remove/rewrite the "exactly one release flagged pre-release" caveat; explain the new
    multi-package loop and the same-commit sanity check (incl. what to do if it fires).
  - Add a **"Publishing a new plugin or mu-plugin"** checklist:
    - set `"private": false` in `package.json` (the only switch `pre-release.sh` checks),
    - a changeset targeting the package is required to trigger a release,
    - build/zip/S3 upload work automatically, no per-plugin script changes needed,
    - **wp-mu-plugin packages are download-only by default** — no `Update URI`-driven
      in-dashboard update, since WordPress core has no update-checker mechanism for must-use
      plugins (see Ticket 4 for the one pilot exception),
    - for `wp-plugin` packages wanting in-dashboard self-update: copy
      `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php`, adjust the
      hardcoded plugin folder/changelog path, and set the `Update URI` header accordingly.
  - Clarify that `@ionos-wordpress/latest` accumulates assets/info.json from every published
    package over time, keyed by filename; a release cycle only touches assets for packages that
    had a fresh prerelease that cycle.
- `scripts/release.sh` header comment — describe the multi-package loop instead of a single
  pre-release → latest handoff.

## Acceptance criteria

- [ ] `docs/7-release.md` no longer states the "exactly one prerelease" limitation as current.
- [ ] The new plugin/mu-plugin onboarding checklist is present and accurate.
- [ ] Script header comments match actual (post-Ticket-1) behavior.

## Notes

Small enough to fold into Ticket 1's PR if a single reviewer/PR is preferred — kept separate here
only for tracking granularity.
