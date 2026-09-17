---
# s501
title: Migrate repository from GitHub to GitLab
status: draft
type: epic
priority: normal
created_at: 2026-08-18T10:16:06Z
updated_at: 2026-08-18T10:16:28Z
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
