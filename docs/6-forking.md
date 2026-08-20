# Forking

Forking creates a separate repository based on the original repository.

Use a fork to develop a feature or fix a bug without changes to the original repository.
This also helps when you work on GitHub pipelines. Fork the repository to test changes before you create a pull request.

To create a working fork, follow these steps:

- Go to https://github.com/IONOS-WordPress/ionos-wordpress/fork and clear the "Copy the `develop` branch only" checkbox.

- Create the fork in your own GitHub account.

Complete a few manual steps to get a full copy of the original repository setup:

- Workflows are disabled by default in a fork. To enable all actions, go to the "Actions" tab of the forked repository and enable the actions.

  If there is no button to enable the actions again, delete the gh_pages action that already ran (see https://github.com/orgs/community/discussions/50736#discussioncomment-11510680).

- Configure GitHub Pages. Go to `{fork_url}/settings/pages` and set the pages to serve from the `gh-pages` branch.

- (Optional) Copy the Dependabot settings from the original repository. Go to `{fork_url}/settings/security-analysis` and configure Dependabot alerts and security updates.

- (Optional) To enable the Google Chat release notification (see `./.github/workflows/release.yaml` and `./scripts/release.sh`), set up the GitHub secret `GCHAT_RELEASE_ANNOUNCEMENTS_WEBHOOK`. This secret must point to the webhook for the wanted Google Chat room. To create a private webhook for testing, follow the instructions at https://developers.google.com/chat/how-tos/webhooks.

- (Optional) To enable the Google Chat pull request notification (see `./.github/workflows/gchat-notify-pull-request.yaml`), set up the GitHub secret `GCHAT_PR_ANNOUNCEMENTS_WEBHOOK`. This secret must point to the webhook for the wanted Google Chat room. To create a private webhook for testing, follow the instructions at https://developers.google.com/chat/how-tos/webhooks.

- (Optional) To do almost everything locally first (for example, releases), [create a fine-grained personal access token](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-fine-grained-personal-access-token).

  **Important**: Create a **New personal access token (classic)** (at https://github.com/settings/tokens) with these scopes:
  - `repo`
  - `workflow`
  - `write:packages`
  - `delete:packages`
  - `project`
  - `read:org`

  Store the generated key in `./secrets`. Use `./.secrets.example` as the template.

  ```
  # provide (classic!) github token for local workflow development or releaasing from local machine
  # required github permissions : repo, workflow, write:packages, delete:packages, project, read:org
  GITHUB_TOKEN='your-generated-key'
  # GitHub CLI (called 'gh') expects the token to be in the GH_TOKEN
  GH_TOKEN="$GITHUB_TOKEN"
  ```
