# Forking

Forking is useful for creating a completely separate repository that is based on the original repository.

A fork can be used to develop a feature or fix a bug without affecting the original repository.
Especially when working on GitHub pipelines, it is useful to fork the repository to test the changes before creating a pull request.

A working fork can be created by following these steps:

- go to https://github.com/lgersman/ionos-wordpress

- press the "Fork" button and create a fork in your individual GitHub account

There are a few manual steps to be done to have a complete setup copy of the original repository:

- Workflows are disabled by default in a fork : To enable all actions, go to the "Actions" tab of the forked repository and enable the actions

  In case there is no button to reenable the actions, simply delete the already runned gh_pages action (see https://github.com/orgs/community/discussions/50736#discussioncomment-11510680)

- configure github pages : go to `{fork_url}/settings/pages` annd configure the pages to be served from the `gh-pages` branch

- (Optional) copy dependabot settings from original repository : go to `{fork_url}/settings/security-analysis` and configure dependabot alerts and security updates

- (Optional) to do almost everything `local first` (for example doing releases), you need to [create a fine-grained personal access token](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-fine-grained-personal-access-token)

  **Important** : You need to create a **New personal access token (classic)** (at https://github.com/settings/tokens) with the following scopes:

  - `repo`
  - `workflow`
  - `write:packages`
  - `delete:packages`
  - `project`
  - `read:org`

  The generated key needs to be stored in `./secrets` (take `./.secrets.example` as boilerplate template) :

  ```
  # provide (classic!) github token for local workflow development or releaasing from local machine
  # required github permissions : repo, workflow, write:packages, delete:packages, project, read:org
  GITHUB_TOKEN='your-generated-key'
  # GitHub CLI (called 'gh') expects the token to be in the GH_TOKEN
  GH_TOKEN="$GITHUB_TOKEN"
  ```
