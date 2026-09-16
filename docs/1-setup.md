## Clone repository

`git clone git@github.com:IONOS-WordPress/ionos-wordpress.git`

## Requirements

Install the most recent version of these tools on your machine:

- `vscode` (tested using version `1.96.0`)
- `docker` (tested using version `27.4.0`)

(Linux only): Make sure you can run the `docker` command without `sudo`.

If `docker run hello-world` does not work, do these steps:

```sh
sudo groupadd docker
sudo gpasswd -a $USER docker
# it might be necessary to reboot your machine here
```

### (MacOS only) Enable SSH Agent Forwarding

_As far as we know, this step is necessary only on MacOS._

To do GIT operations like `git pull`, your SSH keys must be available in the [DevContainer](https://containers.dev/).

You can do this by enabling SSH agent forwarding. See https://www.romanboehm.com/til/vs-code-devcontainer-ssh/

## Installation

- open cloned repository in `vscode`

- `vscode` will automatically ask you to install the required extension
  [`ms-vscode-remote.remote-containers`](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)

  The extension automatically sets up the [DevContainer](https://containers.dev/).

  > A [DevContainer](https://containers.dev/) gives you a full development environment using containers.
  - `vscode` will ask you to open the container. Look for the notification panel at the bottom right of vscode.

- After `vscode` finishes setting up the [DevContainer](https://containers.dev/), click the green corner at the bottom left of `vscode`. Then select `Reopen in Container` to enter the container.

## Get started

- `pnpm start` will start the development server

  This starts the `wordpress-alpine` dev container (see `packages/docker/wordpress-alpine/`) and builds its Docker image first if needed.

- `pnpm stop` will stop the development server

- See the list of all commands: `jq '.scripts' package.json`

## Configuration

- `.env` holds common configuration. This file is committed to git.

- Use `.env.local` for local configuration. This file is not committed to git.

- Use `.secrets` for secret values. This file is not committed to git.

## Project layout

The project layout is designed to host a "product".

A WordPress product can consist of various artifacts, such as plugins and themes. For this reason, the project layout is a monorepo.

- The `packages` directory contains all _buildable_ artifacts.
  - `./packages/wp-plugin` hosts our wordpress plugins
  - `./packages/docker` hosts docker images
  - `./packages/npm` hosts npm packages

- './scripts' hosts **all scripts**.

  The `package.json` scripts section usually references scripts.

  Example: `pnpm test` will execute the `./scripts/test.sh` script.
  - Scripts starting with '\_' are considered "private". Do not run them directly. Other scripts or GitHub Actions use them instead.

  - **every script** contains a header describing what it does, what it requires and what it returns.

- All other top-level directories and files are shared resources across artifacts. This follows the [Single source of truth](https://en.wikipedia.org/wiki/Single_source_of_truth) principle: there is exactly **one** eslint configuration, **one** prettier configuration, **one** `.editorconfig`, and so on.

## Local first

- You can run **every command** **locally and remotely** (in GitHub CI).

- It runs in **exactly the same [DevContainer](https://containers.dev/) environment**.

- The commonly used [DevContainer](https://containers.dev/) environment makes sure that **all tools, configurations, and the underlying OS itself are the same across the different host systems.**

These three rules make development much easier. A script that runs on your machine also runs the same way in CI.

For example:

The `pnpm test` command does many tasks:

- build all WordPress plugins etc.
- start the ephemeral test container
- Execute PHPUnit tests against the test container
- build `Playground` and `Playwright` tests
- execute Playwright tests

As a result, the exact same script runs in the exact same environment, both in the [GitHub Action](https://github.com/IONOS-WordPress/ionos-wordpress/blob/develop/.github/workflows/integration.yaml#L56) and on your local machine.

## Why GitHub

Compared to the in-house GitLab Community Edition at IONOS, **GitHub includes many more built-in tools**:

- GitHub Actions use the same [DevContainer](https://containers.dev/) as your local machine:
  https://github.com/IONOS-WordPress/ionos-wordpress/actions

- You can use [GitHub Releases](https://docs.github.com/en/repositories/releasing-projects-on-github/about-releases) to **distribute our WordPress plugins**: https://github.com/IONOS-WordPress/ionos-wordpress/releases

- [GitHub CI](https://docs.github.com/en/actions/about-github-actions/about-continuous-integration-with-github-actions) lets us run the same `wp-dev` environment in GitHub Actions as on your local machine: https://github.com/IONOS-WordPress/ionos-wordpress/actions

- You can use [GitHub Pages](https://pages.github.com/) to **host configuration files, documentation, and more** for our WordPress plugins: https://ionos-wordpress.github.io/ionos-wordpress/

  [GitHub Pages](https://pages.github.com/) content reflects the `gh_pages` branch of a project. In other words, **versioning is a built-in feature.**

- GitHub hosts a [package registry](https://docs.github.com/en/packages) for
  - `docker` images
  - `npm` packages
  - `ruby` gems
  - `maven` packages
  - _and many more_

## GitHub integration

We use [GitHub CI](https://docs.github.com/en/actions/about-github-actions/about-github-actions) to run tests, create releases, and more.

https://github.com/IONOS-WordPress/ionos-wordpress/tree/develop/.github

Right now the set of workflows in the Monorepo is minimal, but can be extended easily.

> Since we use the same [DevContainer](https://containers.dev/) in GitHub Actions as on your local machine, we can run **the same scripts in GitHub Actions as on your local machine**.

## Fini

![fini](./fini.jpg)
