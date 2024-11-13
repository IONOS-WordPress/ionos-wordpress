---
'ionos-wordpress': patch
---

devcontainer integration

- features

  - [x] Dev Container definition (`./.devcontainer`) providing
    - [x] `pnpm`
    - [x] `composer`
    - [x] `wp-env`
    - [x] `php` including out of the box `xdebug` support
    - [x] SSH access
    - [x] configurable environment (`./.devcontainer/devcontainer.env`)
  - [x] Automatic provisioning of the Dev Container for CI using a GitHub Action
    - [x] including caching of the Dev Container image
  - [x] VSCode Dev Container integration

  Docker in Docker works smoothly in the Dev Container (`wp-env` makes usage of this feature).

  Additional Software can easily be installed/configured into the Dev Container by editing `./.devcontainer/Dockerfile` and or `./.devcontainer/devcontainer.json`.

- GitHub actions

  GitHub action `./.github/workflows/build-devcontainer.yml` is used to build the Dev Container image and push it to the GitHub Container Registry.

  The image is then used in the CI pipeline to run the tests (`./.github/workflows/test-devcontainer.yml`).

- setup using dev container :

  - install vscode

  - install vscode extension `ms-vscode-remote.remote-containers`

  - open vscode and follow the directions or open the devcontainer in vscode

- Snippets

  - execute `composer --version` in dev container : `pnpm exec devcontainer exec --workspace-folder $(pwd) composer --version`

  - execute `pnpm exec devcontainer build --workspace-folder $(pwd)` to rebuild the devcontainer image
