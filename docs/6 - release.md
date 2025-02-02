# release

Releases can be made remote (default) or locally.

If you develop on the release script it's best to fork (see [Forking](./5%20-%20forking.md)) the repository and work on the release script there. Don't worry - it's easy.

# dry run locally

- `pnpm changeset version` will update all affected packages (version and changelog)

  You can now inspect the resulting changes locally

- `pnpm build`

  Now you have EXACTLY the same plugin zips as you would have in a release - check it out !

- unset all local changes : `git checkout .`

# (default) triggering the next release remotely

- push `develop` branch to `main` at GitHub : `git push origin develop:main`

  That's it.

  Now the pipeline (./.github/workflows/release.yml) will be triggered and the release will be created.

  Releasing will create

  - git tags for each published package

  - create / update the changelogs of all affected packages

  - uploads a new release in the GitHub project

  - will also update the `develop` branch to be up to date with `main`. So you can do a `git pull` afterwards in your local repository.

# creating the next release locally

- For releasing locally you need to [create a fine-grained personal access token](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-fine-grained-personal-access-token)

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

# changeset configuration

changeset can be configured using `./.changeset/config.json`

the generated changelog files can be configured to whatever format we like.

# release script

the release script is located in `./scripts/release.sh`

# FAQ

## What happens if i accidently pushed to GitHub 'main' branch instead of 'develop' and triggered a new release ?

**Don't worry** - because the job takes a few minutes you have enough time to stop the jobs.

Stopping the jubs will abort to release process.

You can then revert you acidently pushed changes in `main` and everything is fine.

## Can i revert / delete releases even if I have already published them ?

Yes - you can. The installed `GitHub CLI` (can be executed using `pnpm gh`) has a command to delete releases.

> WordPress has a built-in cron event called wp_update_plugins. This runs Twice Daily (every 12 hours). Note, this does not mean at Midnight and Noon. It depends on when the install was created and/or the last time the cron event ran.
> see https://developer.wordpress.org/reference/functions/wp_update_plugins/

So depending how much time has passed since the release, the plugin will be updated in the next 12 hours from all WordPress installations that have the plugin installed.

In other words - as faster you revert the release, as less installations will be affected by the wrong 'update'.
