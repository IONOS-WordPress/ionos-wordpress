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

# testing a branch end to end in your fork

A fork is the safe place to drive the _real_ GitHub Actions pipelines (pre-release, release, and
anything else that only runs on a push to `main`) against work in progress, before it ever reaches
a pull request against `IONOS-WordPress/ionos-wordpress`. This matters whenever a change touches
the pipelines themselves, or something (like a plugin's self-update mechanism) that can only be
verified once a real release has actually happened - `scripts/release.sh`'s repository-identity
guard (see [release](./7-release.md)) makes this safe: the upstream repository may only publish to
the production S3 folder, and a fork may publish anywhere else, so a misconfigured or buggy run
aborts instead of ever touching production.

- **Push the branch you are testing to your fork's own `main`, not `develop` first**:

  ```
  git push origin my-feature-branch:main
  ```

  This is what actually triggers `pre-release.yml` (it only listens for pushes to `main`), and
  it deliberately skips your fork's `develop` - there is nothing to gain from mirroring the
  branch there first, and doing so would make `develop` diverge from upstream for no reason.

- **Never merge that `main` back into your feature branch, your fork's `develop`, or anywhere
  upstream.** The pipeline commits real, disposable side effects onto it - consumed changesets,
  version bumps, tags - that exist only to drive that one test run. Keeping them confined to
  `main` is what lets you re-run this any number of times without ever touching the branch that
  becomes your actual pull request. If your feature branch itself has a `LINT_ON_PUSH=true`
  pre-push hook, expect it to run on this push too - fix any lint failures it reports and push
  again rather than bypassing the hook.

- **Trigger the manual workflow next**, if the change also depends on it (e.g. `release (manual
workflow)`):

  ```
  gh workflow run "release (manual workflow)" -R <your-fork>/ionos-wordpress --ref main
  gh run watch <run-id> -R <your-fork>/ionos-wordpress
  ```

- **Verify against the real, public output**, not just a green pipeline run. A pipeline can
  succeed while still producing something unusable - see [release](./7-release.md) for what to
  check on S3, and [test](./5-test.md) for verifying a plugin's self-update mechanism against a
  real WordPress instance once the artifacts are out there.

- **Clean up when you're done.** Nothing here needs reverting on your feature branch - it was
  never touched - but you can reset your fork's `main` to drop the disposable commits (e.g.
  `git push --force origin <upstream-main-or-develop>:main`) before the next round, or just
  overwrite it again with the next `git push origin ...:main`.
