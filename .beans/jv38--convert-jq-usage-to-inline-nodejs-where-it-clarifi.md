---
# jv38
title: Convert jq usage to inline Node.js where it clarifies scripts/*.sh
status: todo
type: task
created_at: 2026-08-17T10:25:07Z
updated_at: 2026-08-17T10:25:07Z
parent: 3pr5
---

Opportunistic cleanup: in scripts/_.sh files already touched by the wp-env-to-alpine migration (Phases 2-4), replace jq-based JSON handling with inline node -e (or a short co-located .mjs helper) where it makes a bash block noticeably shorter or more readable. Not a wholesale rewrite — apply only where it genuinely clarifies logic already being touched. Currently ~59 jq call sites across scripts/_.sh; most are likely out of scope since Phases 2-4 are already merged. No behavior change.
