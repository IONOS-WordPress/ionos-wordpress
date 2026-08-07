---
# 5vrh
title: "pnpm lint-fix:i18n fails: deepl rejects input with 'Tag handling parsing failed'"
status: todo
type: bug
priority: normal
created_at: 2026-08-07T10:37:00Z
updated_at: 2026-08-07T10:37:00Z
---

`pnpm lint-fix:i18n` (deepl auto-translation via potrans) aborts on the first `.po` file it
processes. Reproducible on `develop`, unrelated to any current work.

## Reproduce

```bash
pnpm lint-fix:i18n    # needs DEEPL_API_KEY in ./.secrets
```

## Symptom

```
Input: .../packages/wp-mu-plugin/stretch-extra/stretch-extra/languages/stretch-extra-es_ES.po
Translate: from EN to ES
  8/25 [========>-------------------]  32%
ERROR: Bad request, message: Tag handling parsing failed, please check input.
'undefined entity: line 1, column 41' (at Line: 1, Column: 34) on .../deeplcom/deepl-php/src/Translator.php at 779
```

`scripts/lint.sh`'s `ionos.wordpress.dennis` then reports "auto translation failed" and exits 1, so
nothing after the first file is processed.

## Established

- **Pre-existing.** Found while verifying the dual-mode tool dispatch (see [[e6mc]]), but not caused
  by it: `packages/docker/potrans` was untouched by that change (already pinned to PHP 8.4), and the
  failure reproduces identically through the native tool and through the unmodified docker image -
  same file, same deepl message, differing only in the vendor path of the stack line.
- **Not a transient/quota error** - deepl returns a 400 Bad Request about the _input_, consistently.
- The progress differs slightly between runs (5/25 vs 8/25) before failing, so it is likely one
  specific msgid rather than a positional issue.

## Root cause NOT identified

The obvious hypotheses do not hold:

- **HTML entities**: `stretch-extra-es_ES.po` contains no `&` at all - no `&nbsp;`, no `&#NN;`.
- **The `<br />` markup** in the long "BeyondSEO is a comprehensive WordPress SEO plugin..." msgid
  (line 141) is well-formed XML, which deepl's tag handling should accept.

"undefined entity" is an XML parser error, so something in what potrans sends deepl is not valid XML

- but it is not visible in the source file as-is. Next step is to capture the actual request body
  potrans sends (potrans passes `tag_handling` to the deepl API; it may be escaping or re-wrapping the
  msgid) rather than to keep guessing from the `.po` content.

## Notes

- CI never runs this - only `pnpm run lint` (which uses dennis, not potrans) does. So this is a
  developer-facing tool break, not a pipeline break.
- 25 `.po` files are in scope; all are tracked, so `git checkout -- $(git ls-files '*/languages/*.po')`
  restores the tree after an aborted run.
