# release

Releases can be made remote (default) or locally.

If you develop on the release script it's best to fork (see [Forking](./5%20-%20forking.md)) the repository and work on the release script there. Don't worry - it's easy.

## summary

Realising is a 2 step process :

- merge `develop` branch to `main` branch by either creating a PR or just GIT `git push origin develop:main`

  This will create a new release flagged `pre release` at Github

  > **Ensure that at least a single changeset file exists on `develop` before merging to `main` !** Otherwise the pre-release script will fail. You can create a changeset file interactively by executing `pnpm changeset add`

- trigger the release pipeline at GitHub manually

  This will create/update release `@wordpress/latest`.

  This release will contain the release assets of the pre release.

  The `pre release` flagged release will now be flagged `latest`.

That's it.

Release `@wordpress/latest` will provide us

- a permanent url to the latest release ([@wordpress/latest](https://github.com/IONOS-WordPress/ionos-wordpress/releases/tag/%40ionos-wordpress%2Flatest))

- a permanent url to the latest released assets like the [ionos-essentials plugin (ionos-essentials-latest-php7.4.zip)](https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-latest-php7.4.zip)

Caveats:

- The release process assumes that there exists **exactly one** release flagged `pre release`.

  If there are more than one release flagged `pre release` you can manually change the wrongly flagged `pre release` releases at the GitHub project release page.

# dry run locally

- `pnpm changeset version` will update all affected packages (version and changelog)

  You can now inspect the resulting changes locally.

- `pnpm build`

  Now you have EXACTLY the same plugin zips as you would have in a release - check it out !

> Don't forget to revert all changes made by `pnpm changeset version` before committing by unsetting all local changes using `git checkout .`

# create a new prerelease

- push `develop` branch to `main` at GitHub : `git push origin develop:main`

  That's it.

  The pre-release pipeline (`./.github/workflows/pre-release.yml`) will be triggered and the pre release will be created.

  See header of the `./scripts/pre-release.sh` script for a detailed explanation of the pre release process.

  - the newly created pre release is will be [flagged](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository) as `pre-release` in GitHub.

Caveats:

- **a pre release requires **at least one** changeset file. You can create one using `pnpm changeset add`.**

## creating the next pre release locally

- For pre releasing locally you need to [create a fine-grained personal access token](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-fine-grained-personal-access-token)

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

Now you can execute `pnpm pre-release` in the `main` branch locally.

# create a new release

To promote a pre-release to be the official release the monorepo utilize a _floating_ release named `@wordpress/latest`.

Promoting a new release will be done by the pre release pipeline (`./.github/workflows/pre-release.yml`). This pipeline needs to be triggered manually.

- the previously [flagged](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository) as `pre-release` will now be flagged as `latest` in GitHub.

- the release `@wordpress/latest` will have a description saying which release is now the latest one and will have the zip archives ftom the pre release attached. The attached zip archives will not contain the real version number in the name but `latest`

  Example :

  Release `@ionos-wordpress/essentials@0.2.0` is flagged as `pre-release` at GitHub.
  The release contains a asset `ionos-essentials-0.2.0-php7.4.zip`.

  After triggering the release pipeline release `@wordpress/latest` will be updated

  - with the description `latest release is @ionos-wordpress/essentials@0.2.0`

  - attached asset `ionos-essentials-latest-php7.4.zip`. This asset is exactly the same file as `ionos-essentials-0.2.0-php7.4.zip`.

  The release `@ionos-wordpress/essentials@0.2.0` is no more flagged with `pre-release` and now flagged as `latest`.

See header of the `./scripts/release.sh` script for a detailed explanation of the release process.

## creating the next release locally

- For pre releasing locally you need to [create a fine-grained personal access token](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-fine-grained-personal-access-token)

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

  Now you can execute `pnpm release` in the `main` branch locally.

# changeset configuration

changeset can be configured using `./.changeset/config.json`

the generated changelog files can be configured to whatever format we like.

# FAQ

## What happens if i accidently pushed to GitHub 'main' branch instead of 'develop' and triggered a new release ?

**Don't worry** - because the job takes a few minutes you have enough time to stop the jobs.

Stopping the jobs will abort to release process.

You can then revert you acidently pushed changes in `main` and everything is fine.

## Can i revert / delete releases even if I have already published them ?

Yes - you can. The installed `GitHub CLI` (can be executed using `pnpm gh`) has a command to delete releases.

> WordPress has a built-in cron event called wp_update_plugins. This runs Twice Daily (every 12 hours). Note, this does not mean at Midnight and Noon. It depends on when the install was created and/or the last time the cron event ran.
> see https://developer.wordpress.org/reference/functions/wp_update_plugins/

So depending how much time has passed since the release, the plugin will be updated in the next 12 hours from all WordPress installations that have the plugin installed.

In other words - as faster you revert the release, as less installations will be affected by the wrong 'update'.
