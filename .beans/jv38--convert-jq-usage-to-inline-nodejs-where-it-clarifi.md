---
# jv38
title: Convert jq usage to inline Node.js where it clarifies scripts/*.sh
status: completed
type: task
priority: normal
created_at: 2026-08-17T10:25:07Z
updated_at: 2026-08-17T12:26:05Z
parent: 3pr5
---

Opportunistic cleanup: in scripts/_.sh files already touched by the wp-env-to-alpine migration (Phases 2-4), replace jq-based JSON handling with inline node -e (or a short co-located .mjs helper) where it makes a bash block noticeably shorter or more readable. Not a wholesale rewrite — apply only where it genuinely clarifies logic already being touched. Currently ~59 jq call sites across scripts/_.sh; most are likely out of scope since Phases 2-4 are already merged. No behavior change.

## Findings

Scoped to the files actually touched by Phases 2-4 of the wp-env-to-alpine migration (per the
bean's own instruction that most of the ~59 repo-wide jq call sites are out of scope): `start.sh`,
`stop.sh`, `destroy.sh`, `build.sh`, `logs.sh`, `enter.sh`, `cli.sh`, `test.sh`,
`includes/_docker-mounts.sh`. Only two files actually use jq: `build.sh` (18 call sites) and
`test.sh` (2 call sites).

Reviewed every call site:

- Most are single-field extractions (`.name`, `.version`, `.description`, existence checks like
  `jq -e '.scripts.build'`) - jq is already the shortest, clearest form for these; converting to
  Node would need `require()`/property access boilerplate for no gain.
- `test.sh`'s two sites (`select(.php == $php) | .alpine`, `[.[].php] | join(", ")`) are simple,
  idiomatic jq array filtering - not meaningfully clearer in JS.
- One real candidate: `build.sh` had the exact same non-trivial filter
  (`[.dependencies // {}, .devDependencies // {} | to_entries[] | select(.value == "workspace:*") |
.key]|join(" ")`) duplicated verbatim in two functions
  (`index_workspace_packages`/`get_workspace_package_dependency_order`). Extracted it into a shared
  `ionos.wordpress.get_workspace_dependency_names()` Node-based helper, verified identical output
  against jq across every `package.json` in the workspace (no mismatches), and verified `pnpm build`
  and `pnpm lint` both still pass end-to-end with the change in place.

## Reverted after review

On reflection (see this bean's thread), the actual win from that one conversion was marginal: at
each call site it shortened `jq -r \ '...' \ "$PACKAGE_JSON"` (5 lines) to a single function call,
but only by extracting a new 13-line documented helper function - net file size was a wash, not a
genuine reduction. The dedup value (removing a literal duplicate) was real but modest, and readability
gain from JS array/object syntax over jq's `to_entries`/`select` idiom, while real, didn't outweigh
adding a new abstraction for what was otherwise a small amount of duplicated code. Reverted
`scripts/build.sh` to keep the original jq.

## Conclusion: no change

Per the bean's own framing ("opportunistic... only where it genuinely clarifies"), the honest
outcome here is that none of the in-scope jq usage clears that bar strongly enough to justify a
change. No files modified.
