# Ticket 2 — Close the cross-workflow race between pre-release and release

Status: TODO
Depends on: none (can land independently, even before Ticket 1)
Parent plan: [../generalize-plugin-release-mechanism.md](../generalize-plugin-release-mechanism.md) (section 3)
Jira: sub-task of [GPHWPP-4402](https://hosting-jira.1and1.org/browse/GPHWPP-4402)

## Goal

Prevent `pre-release.yml` (push-to-`main` trigger) and `release.yaml` (manual
`workflow_dispatch`) from ever running at the same time, which could otherwise let `release.sh`
read a partial mid-update state of GitHub releases once Ticket 1's "exactly one" safety net is
relaxed.

## Scope

- `.github/workflows/pre-release.yml`
- `.github/workflows/release.yaml`

Give both jobs the same literal, shared `concurrency.group` (e.g. `ionos-wordpress-release-pipeline`)
instead of each using `${{ github.workflow }}-${{ github.ref }}` (which differs per file and
can't collide). Use `cancel-in-progress: false` so the later trigger queues instead of cancelling
the earlier one.

## Acceptance criteria

- [ ] Both workflow YAML files show the same concurrency group string.
- [ ] Triggering `release` while `pre-release` is running (or vice versa) queues rather than runs
      concurrently or cancels the other, confirmed in the Actions UI.

## Notes

Trivial, independent change — good candidate to land first / separately for a fast review.
