---
# 5vrh
title: "pnpm lint-fix:i18n fails: deepl rejects input with 'Tag handling parsing failed'"
status: completed
type: bug
priority: normal
created_at: 2026-08-07T10:37:00Z
updated_at: 2026-08-17T09:05:41Z
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

## Summary of Changes

Root cause found: potrans's stock `DeepLTranslator::getTranslation()` (`packages/docker/potrans/vendor/om/potrans/src/translator/DeepLTranslator.php:28`)
escapes text with `htmlentities($text, ENT_SUBSTITUTE, 'UTF-8')` before sending it to DeepL with
`tag_handling: xml`. `htmlentities()` emits named HTML entities for typographic characters - e.g.
U+2026 (…) becomes `&hellip;`. `&hellip;` is not one of XML's five predefined entities
(`&amp; &lt; &gt; &apos; &quot;`), so DeepL's XML parser rejects the request with "Tag handling
parsing failed ... undefined entity", exactly matching the reported symptom. The failing string was
`stretch-extra-es_ES.po` line 81: `"Validating against blocked plugins…"`.

`vendor/` is composer-installed and gitignored, so the vendor bug can't be patched directly. Fix:
added `packages/docker/potrans/xml-safe-deepl-translator.php`, a custom translator (using potrans's
existing `--translator=<path>` extension point, the same mechanism as the already-present
`DeepLTranslatorEscaped.php` example) that escapes only the characters actually reserved in XML via
`htmlspecialchars($text, ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8')` instead of the full HTML entity table.
Wired it into `scripts/lint.sh`'s `potrans_args` for `lint-fix:i18n`.

Verified via `docker run ionos-wordpress/potrans deepl --translator=packages/docker/potrans/xml-safe-deepl-translator.php ...`
against the previously-failing file: all 25/25 entries translate successfully (was aborting at 5-8/25),
and the ellipsis round-trips correctly (`"Validating against blocked plugins…"` -> `"Comprobación de
los complementos bloqueados…"`).

No changeset: fix is confined to a dev-only tooling script and a new custom-translator file, not a
package's public behavior.
