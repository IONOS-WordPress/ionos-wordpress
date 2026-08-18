---
# sqvg
title: Rewrite docs/ and top-level markdown into STE-compliant prose
status: completed
type: task
priority: normal
created_at: 2026-08-18T10:46:56Z
updated_at: 2026-08-18T10:57:33Z
---

Apply the ste-writing skill (ASD-STE100, STE-flavored mode) to all markdown files under docs/ and the top-level README/AGENTS.md. Excludes CHANGELOG.md (auto-generated) and the ste-writing skill's own files (self-referential).

## Summary of Changes

Rewrote 27 of 30 targeted files into ASD-STE100 STE-flavored prose (active voice, simple tenses, expanded contractions, no semicolons, one topic per sentence/paragraph). 3 files needed no change (already compliant, or contain a verbatim external quote left untouched). All files lint under or near the 2.5-violations-per-100-words flavored target; remaining flags are a linter artifact (blank lines inside code fences or dense bullet lists miscounted as long paragraphs), not real prose violations.

Along the way, fixed pre-existing typos and grammar errors unrelated to STE (e.g. 'WorPress', 'annd', 'toogle', 'the the', British spellings).

One agent stray-edited .gitignore to exclude the very file it changed plus .gitignore itself; reverted that before commit. Also removed a __pycache__ directory left by lint script runs.
