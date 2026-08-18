---
# qrgm
title: 'stretch-extra.sh: ad-hoc PHP dispatch bypasses shared native-tools mechanism'
status: todo
type: task
priority: low
created_at: 2026-08-17T13:38:53Z
updated_at: 2026-08-17T13:38:53Z
parent: qi52
---

ionos.wordpress.stretch-extra.run_php() in scripts/stretch-extra.sh implements its own native-vs-docker dispatch ('command -v php >/dev/null 2>&1') instead of registering 'php' in _native-tools.sh's IONOS_NATIVE_TOOL_PATHS / calling ionos.wordpress.native_tool.

_native-tools.sh's probing logic explicitly documents why it avoids env-sniffing and instead probes for a specific installed binary path, with IONOS_WP_FORCE_DOCKER=1 as an escape hatch. This function bypasses all of that: it uses whatever 'php' is first on $PATH (which may be the wrong version), ignores IONOS_WP_FORCE_DOCKER, and hardcodes 'php:8.3-cli' as its docker fallback image tag inline.

## Impact

If the shared version/escape-hatch logic changes, this script silently doesn't pick it up. A PHP version bump project-wide requires hunting down and fixing this stray '8.3' literal separately, disconnected from image-matrix.json.

## Suggested fix

Register php in IONOS_NATIVE_TOOL_PATHS (or a version-aware equivalent) and route through the shared dispatch instead of a bespoke 'command -v' check.

## Location

scripts/stretch-extra.sh, ionos.wordpress.stretch-extra.run_php() (~line 24-30)
