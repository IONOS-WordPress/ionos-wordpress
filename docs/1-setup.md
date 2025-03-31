## Clone repository

`git clone git@github.com:IONOS-WordPress/ionos-wordpress.git`

## Requirements

You need to have a most recent version of

- `vscode` (tested using version `1.96.0`)
- `docker` (tested using version `27.4.0`)

installed on your machine.

(Linux) : Ensure `docker` command can be executed without being `sudo`.

If `docker run hello-world` does'nt work for you, execute the following steps :

```sh
sudo groupadd docker
sudo gpasswd -a $USER docker
# it might be necessary to reboot your machine here
```

### (MacOS only) Enable SSH Agent Forwarding

_As far as I know this step is only required on MacOS._

To be able to do GIT operations like `git pull` your SSH keys need to be available in the [DevContainer](https://containers.dev/).

This can be achied by enabling SSH agent forwarding : See https://www.romanboehm.com/til/vs-code-devcontainer-ssh/

## Installation

- open cloned repository in `vscode`

- `vscode` will automatically ask you to install the required extension
  [`ms-vscode-remote.remote-containers`](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)

  The extension will automatically handle bootstrapping the [DevContainer](https://containers.dev/)

  > A [DevContainer](https://containers.dev/) provides a full-featured development environment using containers.

  - `vscode` will ask you (notice the notice panel on the right bottom in vsode !) to open into the vscode container.

- Once `vscode` is done bootstrapping the [DevContainer](https://containers.dev/) you can enter the container by clicking on the green bottom left corner of `vscode` and selecting `Reopen in Container`

## Let's go !

- `pnpm start` will start the development server

  Will generate required config files (including vscode launch configuration for debugging and so on) and start `wp-env`.

- `pnpm stop` will stop the development server

- see list of all commands : `jq '.scripts' package.json`

## Configuration

- `.env` for common configuration

  Will be commited.

- `.env.local` can be used for local configuration

  Will not be commited.

- `.secrets` can be used for secrets

  Will not be commited.

- [`.wp-env.override.json`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#wp-env-override-json) can be used to override the default configuration of `wp-env`.

  Will not be commited.

## Project layout

The project layout is designed to host a "product".

A WordPress product may consist of various artifacts like plugins, themes, etc. That's why the project layout is a monorepo.

- All _buildable_ artifacts are located in the `packages` directory.

  - `./packages/wp-plugin` hosts our wordpress plugins
  - `./packages/docker` hosts docker images
  - `./packages/npm` hosts npm packages

- './scripts' hosts **all scripts**.

  Scripts will usually referenced by the `package.json` scripts section.

  Example: `pnpm test` will execute the `./scripts/test.sh` script.

  - scripts starting with '\_' are considered "private" and should not be executed directly. They get either used by other scripts or in GitHub Actions etc.

  - **every script** contains a header describing what it does, what it requires and what it returns.

- all other top-level directories and files are shared resources across artifacts. [Single source of truth](https://en.wikipedia.org/wiki/Single_source_of_truth) : there is exactly **one** eslint configuration, **one** prettier configuration, **one** `.editorconfig` and so.

## Local first

- **Every command** can be executed **locally and remote** (in GitHub CI).

- It will run in **exactly the same [DevContainer](https://containers.dev/) environment**.

- The common used [DevContainer](https://containers.dev/) environment ensures that **all tools, configurations and the underlying OS itself are the same across the different hosts systems.**

These three rules ease the development process massively - if you write a script and it runs at your machine

An Example :

The `pnpm test` command will do heayvy lifting :

- build all WordPress plugins etc.
- spin up `wp-env`
- Execute PHPUnit tests againt the `wp-env` instance
- builds `Playground` and `Playwright` tests
- executes Playwright tests

=> And it's **exactly the same script** executed in **exactly the same environment** in the [GitHub Action](https://github.com/IONOS-WordPress/ionos-wordpress/blob/develop/.github/workflows/integration.yaml#L56) as on your local machine !

## Why GitHub

In contrast to the in-house GitLab Community edition hosted in-house at IONOS **GitHub has "batteries included"** :

- The same [DevContainer](https://containers.dev/) used on your local machine is used in GitHub actions :
  https://github.com/IONOS-WordPress/ionos-wordpress/actions

- [GitHub Releases](https://docs.github.com/en/repositories/releasing-projects-on-github/about-releases) can be used to **distribute our WordPress plugins** : https://github.com/IONOS-WordPress/ionos-wordpress/releases

- [GitHub CI](https://docs.github.com/en/actions/about-github-actions/about-continuous-integration-with-github-actions) is so **powerful**, that we can even run the same `wp-dev` environment in GitHub Actions as on your local machine : https://github.com/IONOS-WordPress/ionos-wordpress/actions

- [GitHub Pages](https://pages.github.com/) can be used to **host configuration files, documentation etc.** for our WordPress plugins : https://ionos-wordpress.github.io/ionos-wordpress/

  [GitHub Pages](https://pages.github.com/) content is reflecting the `gh_pages` branch of a project. In other words : **Versioning is a built-in feature.**

- GitHub hosts a [package registry](https://docs.github.com/en/packages) for

  - `docker` images
  - `npm` packages
  - `ruby` gems
  - `maven` packages
  - _and many more_

## GitHub integration

[GitHub CI](https://docs.github.com/en/actions/about-github-actions/about-github-actions) is used to run tests, do releases and so on.

https://github.com/IONOS-WordPress/ionos-wordpress/tree/develop/.github

Right now the set of workflows in the Monorepo is minimal, but can be extended easily.

> Since we are using the same [DevContainer](https://containers.dev/) in GitHub Actions as on your local machine, we can run **the same scripts in GitHub Actions as on your local machine**.

## Fini

![fini](./fini.jpg)
