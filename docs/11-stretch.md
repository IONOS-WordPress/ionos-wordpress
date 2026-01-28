# Stretch

The monorepo package target `stretch` provides access to stretch hosting platform specific functions.

Since this script (and its documentation) contain internals it is kept in a separate repository https://github.com/IONOS-WordPress/ionos-wordpress-private.

The real stretch script implementation will be downloaded on demand (=> first execution of `pnpm stretch ...`) from the private repo.

You can update/re-download the stretch script implementation at any time using `pnpm stretch --update`

Please refer to https://github.com/IONOS-WordPress/ionos-wordpress-private/blob/main/scripts/README.md for detailed documentation and workflows.

