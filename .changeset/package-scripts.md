---
"ionos-wordpress": patch
---

refactored package scripts to separate shell scripts

  - all `./scripts/*.sh` scripts are _commands_ that can be run from the root of the project.

  - they will load the `.env`/`.env.local` and `.secrets` files before running the appropriate command.

  - `./scripts/includes/bootstrap.sh` is a helper shell include file providing

    - utility functions used by scripts and commands

    - sets up the environment for the commands by injecting the environment read from `./.env`, `./.env.local`, and `./.secrets` files

    _sub package related `.env`, `.secrets_`and `_.env.local_` files can also be placed into the respective sub package directories.
