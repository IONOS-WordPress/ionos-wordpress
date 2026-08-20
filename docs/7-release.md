# release

You can create releases remotely (the default) or locally.

If you develop the release script, fork the repository first (see [Forking](./5%20-%20forking.md)) and work on the release script there. This is easy.

## summary

Releasing is a two-step process:

- Merge the `develop` branch into the `main` branch. Create a PR, or push directly with `git push origin develop:main`.

  This creates a new release flagged `pre release` on GitHub.

  > **Make sure at least one changeset file exists on `develop` before you merge to `main`.** Otherwise the pre-release script fails. Create a changeset file interactively with `pnpm changeset add`.

- Trigger the release pipeline on GitHub manually.

  This creates or updates the release `@wordpress/latest`.

  This release contains the release assets from the pre-release.

  The release flagged `pre release` is now flagged `latest`.

That is all.

Release `@wordpress/latest` gives us:

- a permanent URL to the latest release ([@wordpress/latest](https://github.com/IONOS-WordPress/ionos-wordpress/releases/tag/%40ionos-wordpress%2Flatest))

- a permanent URL to the latest released assets, such as the [ionos-essentials plugin (ionos-essentials-latest-php7.4.zip)](https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-latest-php7.4.zip)

The release process supports any number of packages flagged `pre release` at the same time.
Every non-private package that changed in a release cycle gets its own `pre release`. Triggering
the release pipeline promotes all of them to `@ionos-wordpress/latest` in one run.

Caveats:

- All discovered `pre release` releases must point to the **same commit**. That means they all come
  from one `pre-release.sh` run. The release script checks this first. If the check fails, the
  script stops and shows a clear error that lists the releases that do not match.

  This can happen when a previous `pre-release.sh` run stops or fails, and leaves a stale
  `pre release` for one package. A later run then creates fresh `pre release`s for other
  packages at a different commit. To fix this, delete the stale `pre release` release(s) on
  the GitHub project release page. Or, run `pre-release.sh` again so every package's `pre release`
  matches the current commit.

# dry run locally

- `pnpm changeset version` updates the version and changelog of all affected packages.

  You can now inspect the resulting changes locally.

- `pnpm build`

  Now you have the exact same plugin zips you would get in a release. Review them.

> Revert all changes made by `pnpm changeset version` before you commit. Use `git checkout .` to unset all local changes.

# create a new prerelease

- Push the `develop` branch to `main` on GitHub: `git push origin develop:main`

  That is all.

  This triggers the pre-release pipeline (`./.github/workflows/pre-release.yml`) and creates the pre-release.

  See header of the `./scripts/pre-release.sh` script for a detailed explanation of the pre release process.
  - GitHub [flags](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository) the newly created pre-release as `pre-release`.

Caveats:

- **A pre-release requires at least one changeset file.** Create one with `pnpm changeset add`.

## creating the next pre release locally

- To create a pre-release locally, [create a fine-grained personal access token](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-fine-grained-personal-access-token).

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

Now you can execute `pnpm pre-release` in the `main` branch locally.

# create a new release

To promote a pre-release to the official release, the monorepo uses a floating release named `@ionos-wordpress/latest`.

The `release (manual workflow)` pipeline (`./.github/workflows/release.yaml`) promotes a new release. You must trigger this pipeline manually.

- The pipeline promotes every release currently [flagged](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository) `pre-release` in the same run, not only one. It also removes the `pre-release` flag from each processed release individually.

- The release `@ionos-wordpress/latest` ends up with a description that lists every package promoted in this run. It also gets the zip archives from all of those pre-releases attached. The attached zip archives use `latest` in the name instead of the real version number.

  `@ionos-wordpress/latest` accumulates assets/`<plugin>-info.json` files from every package ever published, keyed by filename. A release cycle only updates the assets that belong to packages with a fresh `pre-release` in that cycle. Assets from packages outside the current cycle stay untouched.

  Example (two packages released in one cycle):

  Releases `@ionos-wordpress/essentials@0.2.0` and `@ionos-wordpress/some-plugin@1.0.0` are both flagged `pre-release` on GitHub, at the same commit.
  They contain assets `ionos-essentials-0.2.0-php7.4.zip` and `some-plugin-1.0.0-php8.3.zip` respectively.

  After you trigger the release pipeline, the release `@ionos-wordpress/latest` updates as follows:
  - The description lists both promoted packages.
  - The attached assets are `ionos-essentials-latest-php7.4.zip` and `some-plugin-latest-php8.3.zip` (exact copies of the versioned zips), plus refreshed `ionos-essentials-info.json` and `some-plugin-info.json`.

  Both `@ionos-wordpress/essentials@0.2.0` and `@ionos-wordpress/some-plugin@1.0.0` are no longer flagged `pre-release`.

See header of the `./scripts/release.sh` script for a detailed explanation of the release process.

## creating the next release locally

- To release locally, [create a fine-grained personal access token](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-fine-grained-personal-access-token).

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

  Now you can execute `pnpm release` in the `main` branch locally.

# publishing a new plugin or mu-plugin

Any `wp-plugin` or `wp-mu-plugin` workspace package can go through the same pre-release, release, and
S3 pipeline described above. One package's release does not block or corrupt another package's release.
To onboard a new package:

- [ ] Set `"private": false` in the package's `package.json`. This is the only switch that
      `pre-release.sh` checks to decide whether a package gets released.
- [ ] A changeset that targets the package is required to trigger a version bump or release. This
      uses the same changeset workflow as any other package. No changes are needed.
- [ ] Build, zip, and S3 upload happen automatically. No per-plugin script changes are needed.
- [ ] **`wp-mu-plugin` packages are download-only by default.** WordPress core has no
      update-checker mechanism for must-use plugins. So mu-plugin releases are published only as
      downloadable and installable artifacts. They never get an `Update URI`-driven update inside
      the dashboard. (`packages/wp-mu-plugin/test-mu-plugin` is a deliberate, scoped pilot
      exception to this rule. See its ticket or plan for details. This does not change the default
      policy for other mu-plugins.)
- [ ] For `wp-plugin` packages that want an in-dashboard self-update, copy
      `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php` into the new
      plugin. Adjust the hardcoded plugin folder name and changelog raw-URL path for the new
      plugin. Then set the plugin's `Update URI` header to
      `https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/<plugin>-info.json`.
      This is a recognized copy-paste pattern, not a shared or parameterized package.

# changeset configuration

You can configure changeset with `./.changeset/config.json`.

You can configure the generated changelog files to any format you like.

# FAQ

## What happens if I accidentally push to the GitHub `main` branch instead of `develop`, and trigger a new release?

**Do not worry.** Because the job takes a few minutes, you have enough time to stop the jobs.

If you stop the jobs, this aborts the release process.

You can then revert the changes you accidentally pushed to `main`. Everything is fine.

## Can I revert or delete releases even if I already published them?

Yes, you can. The installed GitHub CLI (run it with `pnpm gh`) has a command to delete releases.

> WordPress has a built-in cron event called wp_update_plugins. This runs twice daily, about every 12 hours. This does not mean exactly at midnight and noon. It depends on when the install was created, or the last time the cron event ran.
> see https://developer.wordpress.org/reference/functions/wp_update_plugins/

So, depending on how much time has passed since the release, WordPress installations that have the plugin installed will update it within the next 12 hours.

In other words, the faster you revert the release, the fewer installations are affected by the wrong update.
