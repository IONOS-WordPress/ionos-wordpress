---
# hnjy
title: Evaluate replacing @changesets/cli with pnpm's native changeset workflow
status: todo
type: task
created_at: 2026-08-17T10:25:15Z
updated_at: 2026-08-17T10:25:15Z
parent: 3pr5
---

Research whether pnpm's built-in changeset/publish support covers this repo's current bump-type/package-name/multi-package needs (currently handled via @changesets/cli, invoked through scripts/changeset.sh — see package.json:15,67). Outcome may be 'keep changesets' — this is an evaluation, not a guaranteed migration. If pnpm's native support is sufficient, drop the external dependency.
