---
'ionos-wordpress': patch
---

`pnpm build` will build all packages in the workspace.

    - By default the JS/CSS will be transpiled for production.

      To compile to development mode you can set `NODE_ENV=development` before running the build command.

      > [!TIP]
      > You can persist the `NODE_ENV` setting in you personal `.env.local` file. This file will not be commited to the repository.
