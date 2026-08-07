# About

This project is a template for WordPress projects using Dev Containers.

> Dev Containers are a standardized way to define and describe development environments. They are based on Docker containers and can be used with any editor or IDE. Dev Containers are a great way to **ensure that all developers on a project have the same development environment**, regardless of their operating system. The same Dev Container can be used for local development, CI/CD, and more.

It shows how to use [Development Containers](https://containers.dev/) for WordPress plugins/theme development.

_The WordPress plugin in this project is a very rudimentary plugin stub only for showcasing that Dev Containers *just work* for WordPress development (including usage of `wp-scripts` and friends)_

## Features

- [x] Dev Container definition (`./.devcontainer`) providing
  - [x] `pnpm`
- [x] Automatic provisioning of the Dev Container for CI using a GitHub Action
  - [x] including caching of the Dev Container image
- [x] VSCode Dev Container integration

Docker in Docker works smoothly in the Dev Container (the `wordpress-alpine` dev/test containers make usage of this feature).

Additional Software can easily be installed/configured into the Dev Container by editing `./.devcontainer/Dockerfile` and or `./.devcontainer/devcontainer.json`.

## CLI tools: native inside the Dev Container, Docker image outside

The four CLI tools under `packages/docker/` — `ecs-php`, `rector-php`, `potrans` and `dennis-i18n` — exist in two forms, and the scripts pick one automatically:

| where you work                      | which form runs                                            |
| ----------------------------------- | ---------------------------------------------------------- |
| inside the Dev Container, and in CI | natively, from `/opt/ionos-wordpress/tools/<tool>`         |
| outside the Dev Container           | the `ionos-wordpress/<tool>` Docker image, built on demand |

The native form exists because every dockerized invocation costs a Docker-in-Docker round trip, and because a fresh checkout otherwise has to build several Docker images before `pnpm lint` does anything.

Both forms install from the same committed `composer.lock` (and the same pinned `dennis` version), so **they run identical tool versions** — that is what makes the two paths interchangeable. The dispatch lives in `scripts/includes/_native-tools.sh`; it probes for the native executable rather than trying to detect the environment, because `$REMOTE_CONTAINERS`/`$CODESPACES` are not reliably set by `devcontainers/ci`.

> [!IMPORTANT]
> **CI only ever exercises the native path**, so the Docker image path is not covered by automation. This is a deliberate trade, not an oversight. If one of those images breaks — a base image change, a package that disappears from Alpine — nothing will go red; the first person to notice will be a developer working outside the Dev Container.
>
> To verify the Docker path by hand, force it:
>
> ```bash
> IONOS_WP_FORCE_DOCKER=1 pnpm lint
> ```
>
> Please run that before changing anything under `packages/docker/`.

Because the Dev Container image bakes those tools in, its image tag is derived from the tool directories as well as from `./.devcontainer` (see `.github/shared/actions/devcontainer-image-name/action.yaml`). A tool version bump therefore rebuilds the Dev Container image — otherwise CI would keep running the previous tool versions.

## GitHub actions

GitHub action `./.github/workflows/build-devcontainer.yml` is used to build the Dev Container image and push it to the GitHub Container Registry.

The image is then used in the CI pipeline to run the tests (`./.github/workflows/test-devcontainer.yml`).

# Development

## Prerequisites

- docker

- (optional) vscode

## Setup

- `pnpm install`

- `pnpm start`

### VS Code

setup using dev container :

- install vscode

- install vscode extension `ms-vscode-remote.remote-containers`

- open vscode and follow the directions or open the devcontainer in vscode

## Snippets

- execute `composer --version` in dev container : `pnpm exec devcontainer exec --workspace-folder $(pwd) composer --version`

- execute `pnpm exec devcontainer build --workspace-folder $(pwd)` to rebuild the devcontainer image

## Codespaces

[![Open in GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://github.com/codespaces/new?hide_repo_select=true&ref=main&repo=848691489&devcontainer_path=.devcontainer%2Fdevcontainer.json&location=WestEurope)

If you want to use Git hooks for your codespace, then you should set up hooks using the devcontainer.json lifecycle scripts, such as postCreateCommand

# GitLab CI

Dev Container can also be used in GitLab CI. Usage is straightforward and similar to GitHub CI : https://containers.dev/guide/gitlab-ci

# PHPStorm

Dev Container can also be used in PHPStorm : https://www.jetbrains.com/help/phpstorm/connect-to-devcontainer.html

# CLI

Dev Containers can be used in any environment as long as the `docker` prereqisite exists.

There is a NPM package `@devcontainers/cli` managing the usage of Dev Containers in a terminal right at your fingertips : https://containers.dev/guide/cli

As a consequence of the above almost any editing environment can be used with Dev Containers.

# Links

- https://github.com/devcontainers/ci

  A GitHub Action and Azure DevOps Task designed to simplify using Dev Containers (https://containers.dev) in CI/CD systems.

- https://github.com/devcontainers/cli

  A reference implementation for the specification that can create and configure a dev container from a devcontainer.json.
