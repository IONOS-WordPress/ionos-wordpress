---
# u03w
title: Remove the per-job 'npm install -g @devcontainers/cli' cost (1-12s)
status: todo
type: task
priority: normal
created_at: 2026-08-07T11:56:33Z
updated_at: 2026-08-07T11:56:33Z
parent: 1qc9
---

`devcontainer-shell-run` installs the devcontainer CLI on the runner because there is no host-side
`node_modules` (`pnpm install` only ever runs inside the container). Measured in run 31175438431 at
**1s in the lint job and 12s in the build job** - the largest uncontrolled cost left in the setup
path, and one that only exists because [[ytd4]] moved off devcontainers/ci, which bundles the CLI.

Eliminating it tips [[ytd4]] from neutral to a genuine ~9s/job win.

## First: get more samples

Two data points 1s and 12s apart is not enough to pick a fix. The timing line is already in the log
(`timing: devcontainer CLI install took Ns`) - read it over the next several runs before choosing.

## Options

- `actions/cache` over the npm global prefix, keyed on the CLI version. Careful: the composite
  action runs 3-4x per job, so a cache step inside it would pay a restore each time and could easily
  cost more than the install. It belongs once per job in the workflow, which spreads the logic out.
- Pre-install the CLI into a tiny host-side location and cache that.
- Reconsider whether the CLI is needed for every invocation: only the first one needs `up`, later
  steps could use a plain `docker exec` against the known container. That removes the CLI from the
  hot path but not from the first step, which is where the cost is.

## Do not

Hand-roll the `docker run` line from the image's `devcontainer.metadata` label. It carries
privileged, the docker-in-docker entrypoint, volume mounts and remoteUser, and getting that subtly
wrong is exactly the class of bug that is invisible until a test fails for an unrelated-looking
reason.
