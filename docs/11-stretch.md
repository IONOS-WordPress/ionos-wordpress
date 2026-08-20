# Stretch

The monorepo package target `stretch` gives access to functions specific to the stretch hosting platform.

This script, and its documentation, contain internal details. So the project keeps them in a separate repository, https://github.com/IONOS-WordPress/ionos-wordpress-private.

The real stretch script implementation downloads on demand, at the first run of `pnpm stretch ...`, from the private repo.

You can update or re-download the stretch script implementation at any time with `pnpm stretch --update`.

See https://github.com/IONOS-WordPress/ionos-wordpress-private/blob/main/scripts/README.md for detailed documentation and workflows.
