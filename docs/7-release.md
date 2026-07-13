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

The release process supports **any number** of packages flagged `pre release` at once - every
non-private package that changed in a release cycle gets its own `pre release`, and triggering
the release pipeline promotes **all** of them to `@ionos-wordpress/latest` in one run.

Caveats:

- All discovered `pre release` releases must point at the **same commit** (i.e. all come from one
  `pre-release.sh` run). The release script checks this before doing anything and aborts with a
  clear error listing the offending releases if it doesn't hold.

  This can happen if a previous `pre-release.sh` run was aborted/broken and left a stale
  `pre release` behind for one package while a later run created fresh `pre release`s for other
  packages at a different commit. To fix it, either delete the stale `pre release` release(s) at
  the GitHub project release page, or re-run `pre-release.sh` so every package's `pre release`
  is regenerated at the current commit.

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

To promote a pre-release to be the official release the monorepo utilize a _floating_ release named `@ionos-wordpress/latest`.

Promoting a new release is done by the `release (manual workflow)` pipeline (`./.github/workflows/release.yaml`). This pipeline needs to be triggered manually.

- every release currently [flagged](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository) `pre-release` is promoted in the same run - not just one. Each processed release is individually un-flagged as `pre-release`.

- the release `@ionos-wordpress/latest` ends up with a description listing **every** package promoted this run, and will have the zip archives from all of those pre-releases attached. The attached zip archives will not contain the real version number in the name but `latest`.

  `@ionos-wordpress/latest` **accumulates** assets/`<plugin>-info.json` files from every package ever published, keyed by filename - a release cycle only touches/updates the assets belonging to packages that had a fresh `pre-release` in that cycle; assets from packages not part of the current cycle are left untouched.

  Example (two packages released in one cycle):

  Releases `@ionos-wordpress/essentials@0.2.0` and `@ionos-wordpress/some-plugin@1.0.0` are both flagged `pre-release` at GitHub, both at the same commit.
  They contain assets `ionos-essentials-0.2.0-php7.4.zip` and `some-plugin-1.0.0-php8.3.zip` respectively.

  After triggering the release pipeline, release `@ionos-wordpress/latest` will be updated:
  - with a description listing both promoted packages,
  - with attached assets `ionos-essentials-latest-php7.4.zip` and `some-plugin-latest-php8.3.zip` (exact copies of the versioned zips), plus refreshed `ionos-essentials-info.json` and `some-plugin-info.json`.

  Both `@ionos-wordpress/essentials@0.2.0` and `@ionos-wordpress/some-plugin@1.0.0` are no longer flagged `pre-release`.

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

# publishing a new plugin or mu-plugin

Any `wp-plugin` or `wp-mu-plugin` workspace package can go through the same pre-release → release →
S3 pipeline described above, without one package's release blocking or corrupting another's. To
onboard a new package:

- [ ] set `"private": false` in the package's `package.json` - this is the **only** switch
      `pre-release.sh` checks to decide whether a package gets released.
- [ ] a changeset targeting the package is required to trigger a version bump/release - same
      changeset workflow as any other package, no changes needed.
- [ ] build, zip and S3 upload happen automatically - no per-plugin script changes are needed.
- [ ] **`wp-mu-plugin` packages are download-only by default.** WordPress core has no
      update-checker mechanism for must-use plugins, so mu-plugin releases are published as
      downloadable/installable artifacts only - they never get an `Update URI`-driven
      in-dashboard update. (`packages/wp-mu-plugin/test-mu-plugin` is a deliberate, scoped pilot
      exception to this - see its ticket/plan for details. This is not a change to the default
      policy for other mu-plugins.)
- [ ] for `wp-plugin` packages that want in-dashboard self-update, copy
      `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php` into the new
      plugin, adjust the hardcoded plugin folder name / changelog raw-URL path for the new
      plugin, and set the plugin's `Update URI` header to
      `https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/<plugin>-info.json`.
      This is a recognized copy-paste pattern, not a shared/parameterized package.

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
