# Local workflow development

Local GitHub workflow development frees you from commit and push cycles to test your workflows.

Local GitHub workflow development can be done using https://github.com/nektos/act.

## Setup

- Create a `./.secrets` file containing a [GitHub personal access token (classic)](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens) : `GITHUB_TOKEN=your_token`.

  _See https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry#authenticating-with-a-personal-access-token-classic for reasons why the classic token type is required_

  _Ensure **not committing** the `./.secrets` file to GIT._

- Act can be used on both Linux host machine and inside the dev container:

  - in local Linux host machine

    (Option 1) Install act using the following command:

    ```bash
    curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash
    # see https://nektosact.com/installation/index.html#bash-script
    ```

    (Option 2)  If your Linux is in the docker group you can use the following command:

    ```bash
    curl https://raw.githubusercontent.com/nektos/act/master/install.sh | bash
    ```

    start act using the following command:

    ```bash
    ./bin/act
    ```

    In case of any error connecting to docker, you can start the docker service using the following command:

    ```bash
    systemctl start docker
    ```
    Repeat the act command after starting the docker service to ensure everything is working fine.

    Now you can use act to run the workflows locally. For example to trigger the `release` workflow:

    ```bash
    ./bin/act
    # or more explicit
    ./bin/act push
    ```

  - inside the dev container (preferred way)

    - authenticate to GitHub using the GitHub CLI:

    ```bash
    (source ./.secrets && echo $GITHUB_TOKEN) | gh auth login --with-token
    # or alternatively
    source ./.secrets
    # will consume GitHub token from GH_TOKEN environment variable
    gh auth login
    ```
    - install act as gh extension

    ```bash
    # gh ist the GitHub CLi already preinstalled in the dev container
    # see https://nektosact.com/installation/gh.html
    gh extension install https://github.com/nektos/gh-act
    ```

    > [!TIP]
    > if you have provided the GH_TOKEN in the `.secrets` file you can use the following command to skip GitHub authentication using `gh auth login`:
    > `source ./.secrets && gh extension install https://github.com/nektos/gh-act`

    - start act the first time using the following command:

    ```bash
    gh act
    ```

    At first time act will download the act container image.

    _Choose the medium image type if act asks you to choose._

    - Now you can use act to run the workflows locally. For example to trigger the `build` workflow:

    ```bash
    gh act
    # or more explicit
    gh act push
    ```

# Links

- see https://nektosact.com/usage/index.html for detailed commandline actions


