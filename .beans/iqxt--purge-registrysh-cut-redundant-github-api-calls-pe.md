---
# iqxt
title: 'purge-registry.sh: cut redundant GitHub API calls (per-package version count + org check)'
status: todo
type: task
priority: low
created_at: 2026-08-17T13:39:42Z
updated_at: 2026-08-17T13:39:42Z
parent: qi52
---

Two inefficiencies in scripts/purge-registry.sh:

1. For every matched package, the script issues a separate paginated 'gh api .../versions' call (~line 147-148) purely to print a cosmetic "(N version(s))" line in the dry-run/delete listing. The actual deletion (~line 172-175) needs no version count at all - it deletes the whole package in one DELETE call per package.

2. The script calls '"$GH" api "/orgs/${OWNER}"' (~line 118-124) to decide which of two list endpoints to use, then immediately calls '--paginate "$LIST_ENDPOINT"' - two sequential round-trips before any real work starts.

## Impact

In an org with many stale packages (exactly this script's stated cleanup use case), every purge run pays '1 (org check) + 1 (paginated list) + N (per-package version count) + N (per-package delete)' GitHub API calls where '1 + 1 + N' would suffice. This roughly doubles the API calls needed and increases exposure to GitHub's rate limiting in what is explicitly a registry-cleanup/CI context.

## Suggested fix

Drop the per-package version-count fetch (or fold it into the same call used elsewhere), and cache/skip the org-membership check where possible.

## Location

scripts/purge-registry.sh:118-124, 147-148
