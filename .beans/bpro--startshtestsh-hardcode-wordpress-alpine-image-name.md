---
# bpro
title: start.sh/test.sh hardcode wordpress-alpine image name, ignoring DOCKER_USERNAME/DOCKER_REPOSITORY overrides
status: completed
type: bug
priority: normal
created_at: 2026-09-08T08:13:26Z
updated_at: 2026-09-08T08:14:29Z
---

pnpm start hardcodes 'ionos-wordpress/wordpress-alpine:latest' in scripts/start.sh instead of deriving it via ionos.wordpress.docker_image_name_for_package. If a developer has DOCKER_USERNAME/DOCKER_REPOSITORY set in their environment (generic-sounding var names, easily collide with unrelated tooling), pnpm build tags the locally built image under that overridden name while start.sh still looks for the hardcoded default name, doesn't find it, and docker silently falls back to a Docker Hub pull attempt - failing with a confusing 'pull access denied for ionos-wordpress/wordpress-alpine' error. scripts/test.sh has the same hardcoded name (with an existing guard/comment describing this exact failure) but doesn't derive it either. Fix: use ionos.wordpress.docker_image_name_for_package (scripts/includes/_bootstrap.sh) in both scripts so the run/pull target always matches whatever build.sh actually tagged.

## Summary of Changes

Fixed by deriving WORDPRESS_ALPINE_IMAGE via ionos.wordpress.docker_image_name_for_package in both scripts/start.sh and scripts/test.sh, instead of hardcoding 'ionos-wordpress/wordpress-alpine:latest'. Now the run/pull target always matches whatever scripts/build.sh actually tagged, even when a developer has DOCKER_USERNAME/DOCKER_REPOSITORY set in their environment. Verified the function resolves correctly both with and without those overrides set, and that both scripts still pass bash -n.
