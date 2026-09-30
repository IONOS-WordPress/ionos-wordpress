---
# s501
title: Migrate repository from GitHub to GitLab
status: draft
type: epic
priority: normal
created_at: 2026-08-18T10:16:06Z
updated_at: 2026-09-18T11:06:12Z
---

Move the ionos-wordpress monorepo from GitHub to GitLab (repo, CI/CD, issues/PRs, container registry usage, and related tooling).

This is a **draft** — motivation, target timeline, and whether it's a full
cutover vs. a mirrored/parallel state aren't established yet. Needs
refinement before any child work starts.

## What's currently GitHub-shaped in this repo

- **CI/CD**: `.github/workflows/*.yaml` (build-devcontainer-image,
  build-wordpress-alpine-image, integration, pre-release, release,
  gchat-notify-pull-request), plus shared composite actions under
  `.github/shared/actions/` and scripts under `.github/shared/scripts/`.
  Uses GitHub Actions marketplace actions (`actions/checkout`,
  `actions/cache`, `docker/build-push-action`, `dependabot/fetch-metadata`,
  `devcontainers/ci`, `pnpm/action-setup`, ...) with no direct GitLab CI
  equivalents assumed - each needs a mapping.
- **`gh` CLI usage**: `scripts/gh-cli.sh` and several scripts
  (`pre-release.sh`, `release.sh`, `purge-registry.sh`,
  `update-dependencies.sh`, `stretch.sh`, `build.sh`, `test.sh`, `lint.sh`,
  `beans.sh`) shell out to `gh` for PRs/releases/registry - each call site
  needs a `glab` (or GitLab API) equivalent.
- **Container registry**: images currently pushed to GHCR
  (`IMAGE_REGISTRY`/`IMAGE_REPOSITORY` Actions variables, defaulting to
  ghcr.io) - would move to the GitLab Container Registry.
- **Dependabot**: `dependabot/fetch-metadata` implies Dependabot config -
  GitLab's equivalent is its own Dependency Scanning / Renovate.
- **Notifications**: `gchat-notify-pull-request.yaml` posts PR events to
  Google Chat - needs the GitLab merge-request webhook equivalent.
- **Beans**: `.beans/` bean files sometimes reference GitHub PR numbers /
  issues in their history - not itself GitHub-coupled, but worth checking
  for hardcoded GitHub links during migration.
- **Plugin-side GitHub Releases fallback (field-deployed, not just CI tooling)**:
  `ionos-essentials` and `ionos-core` ship with a hardcoded `LEGACY_INFO_JSON_URL`
  pointing at `github.com/IONOS-WordPress/ionos-wordpress/releases/...` as a
  fallback update source (see epic [[serve-plugin-updates-from-s3-instead-of-github-releases]]).
  This runs on real, already-installed WordPress sites, not in CI - a `gh`→`glab`
  tooling port does not touch it. It only goes away once bean 4du2 ("Retire the
  GitHub release delivery path once S3 delivery is proven") ships and rolls out,
  which is itself gated on S3 delivery being observed working in production for a
  while. A full cutover (GitHub repo archived/inaccessible) must not happen before
  that, or every installation still relying on the fallback loses updates with no
  way to self-heal.

## Open questions (need answers before this leaves draft)

- [ ] Why is this migration happening (org policy, cost, tooling
      consolidation, other)? What's driving it?
- [ ] Full cutover (GitHub repo archived/read-only) or GitLab becomes
      primary while GitHub stays as a mirror?
- [ ] Target timeline / is there a deadline forcing this?
- [ ] Does issue/PR history (including linked Dependabot alerts, GitHub
      Discussions if used) need to be migrated, or is a clean start on
      GitLab acceptable?
- [ ] Self-managed GitLab or GitLab.com/SaaS? Affects available CI runners,
      registry, and integrations.
- [ ] Who owns secrets/credentials rotation for the new platform (registry
      creds, notification webhooks, any deploy keys)?
- [ ] Should this be one big-bang cutover or a phased migration
      (e.g., CI first, then issues, then final repo move)?

## Likely scope once refined (not yet broken into child beans)

- [ ] Repo/history migration (git mirror or import)
- [ ] Rewrite `.github/workflows/*.yaml` as `.gitlab-ci.yml` + includes
- [ ] Replace `gh` CLI call sites with `glab`/GitLab API equivalents
- [ ] Container registry cutover (GHCR → GitLab Container Registry)
- [ ] Dependabot → GitLab Dependency Scanning/Renovate equivalent
- [ ] PR notification webhook → GitLab MR webhook
- [ ] Update AGENTS.md / docs/agent/git-conventions.md references to
      GitHub-specific conventions if any
- [ ] Branch protection rules, required checks, CODEOWNERS equivalent
- [ ] Confirm no plugin still relies on the `LEGACY_INFO_JSON_URL` GitHub Releases
      fallback (blocked on 4du2) before any full cutover that would make
      `github.com/IONOS-WordPress/ionos-wordpress` inaccessible
