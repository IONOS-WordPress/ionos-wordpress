---
"ionos-wordpress": patch
---

- added configurable environment support

  - the `./.env` file is injected into every `./scripts/*.sh` command

    this file is supposed to be checked into the repository and contains default environment variables.

    this file **should not contain sensitive information**.

  - the optional `./secrets` file is injected into every `./scripts/*.sh` command

    this file is **not checked into the repository** and **contains sensitive information like tokens, passwords and so on**.

    see `./.secrets.example` for usage.

  - the optional `./.env.local` file may be used to override the default environment variables.

    use it to customize your local development environment.

    this file **will not checked into the repository**.

    see `./.env.local.example` for usage.
