---
# 9vhi
title: Integrate ste-writing skill for STE-compliant prose
status: completed
type: task
priority: normal
created_at: 2026-08-18T10:30:16Z
updated_at: 2026-08-18T10:32:44Z
---

Vendor the ste-writing skill (ASD-STE100 Simplified Technical English rewriting) from
https://github.com/woosal1337/blog/tree/main/videos/ep01-the-cure-for-ai-slop into this repo
as a first-class skill, following the docs/skills/<name>/SKILL.md convention already used by
the testing and stretch skills.

## Scope
- Copy `ste-writing-skill.md` -> `docs/skills/ste-writing/SKILL.md`
- Copy `ste-lint.py` -> `docs/skills/ste-writing/ste-lint.py` (referenced by the skill's Verify section)
- Copy `ste-recurring-errors.md` -> `docs/skills/ste-writing/ste-recurring-errors.md` (referenced by the skill's strict mode)
- Adjust the skill's relative links/paths if needed once colocated under docs/skills/ste-writing/
- Register the skill in the agent docs so it is discoverable:
  - Add a mention in AGENTS.md (or docs/agent/ as appropriate) pointing at docs/skills/ste-writing/SKILL.md
  - Confirm .gemini/skills picks it up automatically (docs/skills/README.md states .gemini/skills is directly linked from docs/skills)
  - Confirm .claude/settings.json's \`skillDirs: [\"./docs/skills\"]\` already covers discovery, no config change expected

## Source
- Skill: https://github.com/woosal1337/blog/blob/main/videos/ep01-the-cure-for-ai-slop/ste-writing-skill.md
- Lint script: https://github.com/woosal1337/blog/blob/main/videos/ep01-the-cure-for-ai-slop/ste-lint.py
- Recurring errors reference: https://github.com/woosal1337/blog/blob/main/videos/ep01-the-cure-for-ai-slop/ste-recurring-errors.md
- License/attribution: source repo has no explicit license file spotted yet - verify before vendoring, and keep attribution to the original author (woosal1337) in the copied files.

## Todo
- [ ] Verify source repo licensing permits vendoring
- [ ] Copy the 3 files into docs/skills/ste-writing/
- [ ] Fix any relative paths/links between the copied files
- [ ] Add discovery entry in AGENTS.md / docs/agent/
- [x] Smoke-test: invoke the skill on a sample doc, run ste-lint.py against the result

## Summary of Changes

- Verified licensing: source repo (woosal1337/blog) is MIT-licensed for code, author Ege Çelebi.
- Copied `ste-writing-skill.md` -> `docs/skills/ste-writing/SKILL.md`, `ste-lint.py`, and `ste-recurring-errors.md` unchanged (relative link to ste-recurring-errors.md already resolves since colocated).
- Added a Source/attribution line to SKILL.md pointing at the upstream repo and this bean.
- Registered the skill in `docs/10-ai-integration.md` (the actual discovery doc AGENTS.md's skills mention resolves to) with a new "### STE writing skill" section mirroring the existing Testing skill section, including linter usage examples.
- No changes needed to .claude/settings.json (skillDirs already covers docs/skills) or .gemini/skills (directly linked from docs/skills).
- Smoke-tested `ste-lint.py` against a slop-laden sample paragraph - it correctly flagged 9 violations (33.33/100 words), well above the flavored target of <2.5/100.
