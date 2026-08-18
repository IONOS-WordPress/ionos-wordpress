---
# iqxt
title: 'purge-registry.sh: cut redundant GitHub API calls (per-package version count + org check)'
status: completed
type: task
priority: low
created_at: 2026-08-17T13:39:42Z
updated_at: 2026-08-18T09:25:41Z
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

## Summary of Changes

1. Merged the org-membership check into the package-list call itself: instead of a separate `gh api /orgs/${OWNER}` probe followed by a paginated list call, the script now attempts the org packages endpoint directly (a nonexistent/foreign org 404s there too) and falls back to the user endpoint only on failure - one round trip instead of two in the common (org) case.
2. Eliminated the N per-package version-count fetches entirely, not just folded them in: GitHub's package-list API response already includes each package's `version_count` field. Changed the list call's `--jq` filter to extract `[name, version_count]` as tab-separated lines, and read both into a `VERSION_COUNTS` associative array during the existing matching loop - the per-package DELETE listing now reads from that array instead of making a separate `.../versions` API call per package.

Net result: '1 (org+list combined) + N (delete, only with --yes)' API calls instead of '1 (org check) + 1 (list) + N (version count) + N (delete)'.

## Verification

This script is destructive (deletes GitHub container registry packages, needs a delete:packages-scoped token) - did NOT run it against the real registry. Instead:
- Verified `bash -n` syntax.
- Verified the `--jq '.[] | [.name, .version_count] | @tsv'` filter against a crafted JSON fixture matching GitHub's documented package-list schema.
- Extracted the full matching/counting loop logic and ran it against a mocked `gh` CLI returning that fixture - confirmed matched/skipped classification, legacy-devcontainer-pattern matching, and per-package version counts all come out identical to the original logic, with zero per-package API calls.
- Separately verified the org->user endpoint fallback path with a mock that 404s the org endpoint.
