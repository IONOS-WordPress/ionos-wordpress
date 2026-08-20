---
# o1oo
title: Unify the 'native tool or docker fallback' pattern (5 near-duplicate copies, already diverged)
status: completed
type: task
priority: normal
created_at: 2026-08-17T13:38:40Z
updated_at: 2026-08-18T08:23:34Z
parent: qi52
---

The 'try native tool via ionos.wordpress.native_tool, else docker run <image>' fallback shape is copy-pasted near-verbatim across 5 call sites: ecs (scripts/lint.sh:106), potrans (scripts/lint.sh:227), dennis (scripts/lint.sh:269), rector (scripts/build.sh), and rector-fix-types.sh.

The copies have already silently diverged: potrans forwards DEEPL_API_KEY inline in its docker fallback branch, dennis does not.

## Impact

A change to the fallback contract (e.g. adding a shared error message, a retry, or a new env var that must be forwarded in both branches) has to be replicated across 5 places by hand. The pattern already shows real drift, making it easy to miss updating one of the copies during a future fix.

## Suggested fix

A shared helper in _native-tools.sh, e.g. 'ionos.wordpress.run_native_or_docker <tool> <docker_image> -- <args...>', that both branches funnel through, with per-call env exported by the caller before invoking it.

## Location

scripts/lint.sh:106,227,269
scripts/build.sh (rector block)
scripts/rector-fix-types.sh:14

## Summary of Changes

Added a shared `ionos.wordpress.run_native_or_docker()` helper in `scripts/includes/_native-tools.sh` and used it for the 3 call sites whose argument list is already identical between native/docker modes: ecs (scripts/lint.sh), potrans (scripts/lint.sh), dennis (scripts/lint.sh). Per-call differences (potrans's `DEEPL_API_KEY` forwarding, ecs's `COMPOSER_HOME`/`--user`) are passed in by the caller (env-var prefix for native-mode env, a nameref'd array of extra `docker run` flags for docker-mode differences) rather than duplicated inline.

## Scope decision

Left rector's 2 call sites (scripts/build.sh, scripts/rector-fix-types.sh) unchanged - their native and docker branches construct genuinely different arguments (docker mounts a synthetic `/project/dist` + a separately mounted config file; native addresses real host paths directly), not just an env-var difference. Unifying those cleanly would require the helper to also abstract volume-mount/path translation, which is more invasive and touches the production plugin-packaging pipeline (`scripts/build.sh`) - decided with the user to keep this fix to the 3 genuinely-identical call sites.

## Verification

- `pnpm lint` (native path): PHP (ecs) and i18n (dennis) linting both pass.
- `IONOS_WP_FORCE_DOCKER=1 pnpm lint --use php` and `--use i18n`: docker fallback path verified for both ecs and dennis (built and ran their docker images).
- `pnpm build`: full build passes, confirming the shared `_native-tools.sh` change doesn't disturb the untouched rector call sites.
