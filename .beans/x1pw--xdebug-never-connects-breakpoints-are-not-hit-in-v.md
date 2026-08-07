---
# x1pw
title: 'xdebug never connects: breakpoints are not hit in vscode'
status: completed
type: bug
created_at: 2026-08-06T14:54:26Z
updated_at: 2026-08-06T14:54:26Z
---

The generated `.vscode/launch.json` was correct, but the `wp-alpine` image's xdebug
configuration made a connection impossible, so no breakpoint was ever hit.

Two independent blockers, both verified by reproducing them against a probe build:

1. `xdebug.client_host` was the packaged default `localhost`. Xdebug connects _out_ of the
   container to the IDE, so `localhost` pointed at the container itself. `host.docker.internal`
   was additionally not resolvable inside the container (no `--add-host` on `docker run`).
2. `xdebug.start_with_request` was the packaged default `default`, which for mode `debug`
   only starts a session when an `XDEBUG_TRIGGER`/`XDEBUG_SESSION` cookie or query parameter
   is present. The xdebug log showed: "Trigger value for 'XDEBUG_SESSION' not found, so not
   activating". The wp-env setup this image replaced used `yes`.

- [x] pin `xdebug.client_host=host.docker.internal`, `client_port=9003`, `start_with_request=yes` in the Dockerfile
- [x] add `--add-host host.docker.internal:host-gateway` to the `docker run` in scripts/start.sh
- [x] same for the ephemeral test container in scripts/test.sh (so `pnpm test:php` is debuggable)
- [x] verify a DBGp init packet actually reaches a listener on the host's port 9003
- [x] document the behaviour in README.md and docs/3-tools.md

## Summary of Changes

`packages/docker/wp-alpine/Dockerfile` appends the three settings to the distro's
`50_xdebug.ini`; `scripts/start.sh` and `scripts/test.sh` map `host.docker.internal` to the
host gateway. Verified end-to-end: with only the baked-in config (no `-d` overrides) the
container logs "Connected to debugging client: host.docker.internal:9003" and a host-side
listener receives the DBGp `<init>` packet.

Note: `start_with_request=yes` means every request attempts a connection. When no IDE is
listening the connection is refused immediately, so the cost is negligible.
