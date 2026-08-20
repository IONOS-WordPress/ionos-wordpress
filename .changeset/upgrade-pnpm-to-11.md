---
'ionos-wordpress': major
---

BREAKING: upgrade pnpm from 9.15.9 to 11.22.0. Native Linux users not using the dev container must manually update their local pnpm install to match:

    curl -fsSL https://get.pnpm.io/install.sh | env PNPM_VERSION=11.22.0 sh -

pnpm-specific settings moved out of `.npmrc` (now deleted) into `pnpm-workspace.yaml` as camelCase keys, and dependency install scripts (eg. Playwright's browser download) now require explicit allowlisting via `allowBuilds` - both pnpm 11 behavior changes with no pnpm 9 equivalent.
