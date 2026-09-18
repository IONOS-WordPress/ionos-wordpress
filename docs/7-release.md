# release

You can create releases remotely (the default) or locally.

If you develop the release script, fork the repository first (see [Forking](./6-forking.md)) and work on the release script there. This is easy.

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
- [ ] **`wp-mu-plugin` packages get no `Update URI`-driven update, because WordPress core has no
      update-checker mechanism for must-use plugins at all** - there is no `Update URI` header, no
      `update_plugins_<host>` filter, and no entry in the updates screen for them. That does not
      mean mu-plugins can't self-update: `ionos-core` implements its own, entirely independent
      mechanism (`packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php`) that hooks the
      `wp_update_plugins` cron event, fetches its own update descriptor, compares the result
      against its own `Version` header (read via `get_file_data()`), and installs through a custom
      `MU_Plugin_Upgrader` (a `WP_Upgrader` subclass) if a newer version is found. To add the same
      mechanism to a new mu-plugin, copy that file, adjust the two `INFO_JSON_URL`/
      `LEGACY_INFO_JSON_URL` constants (S3 first, GitHub fallback - see below) and the path passed
      to `get_file_data()`. This is a recognized copy-paste pattern, not a shared package.
- [ ] For `wp-plugin` packages that want an in-dashboard self-update, copy
      `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php` into the new
      plugin. Adjust the hardcoded plugin folder name and changelog raw-URL path for the new
      plugin. Then set the plugin's `Update URI` header to
      `https://s3-de-central.profitbricks.com/web-hosting/__S3_FOLDER__/<plugin>-info.json` -
      `scripts/build.sh` substitutes the `__S3_FOLDER__` placeholder at build time (see
      [build](./2-build.md)). Keep the plugin's `LEGACY_INFO_JSON_URL` constant pointing at
      `https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/<plugin>-info.json`
      as the GitHub fallback (see "S3-first, GitHub-fallback resolution" above). This is a
      recognized copy-paste pattern, not a shared or parameterized package.

# the S3 release target

Every release also mirrors its assets to IONOS S3 object storage, so plugins can resolve their
updates from S3 instead of from GitHub releases.

- **Bucket**: `web-hosting` (endpoint `https://s3-de-central.profitbricks.com`), publicly readable
  over HTTPS.
- **Folder**: configurable via the `S3_FOLDER` environment variable (see `.env`). Production
  releases publish to `ionos-group`. A fork can publish to a throwaway folder instead (`test` is
  the convention) by setting the `S3_FOLDER` repository variable in its own GitHub Actions
  settings - see "test-phase releases in a fork" below.

  `scripts/release.sh` refuses any other combination of repository and folder: the upstream
  repository may only publish to `ionos-group`, and a fork may publish to anything except
  `ionos-group`. This guards against either shipping test artifacts to real users or a fork
  overwriting production assets.

- **File listing**: for every released zip, S3 ends up holding the same three names GitHub does,
  plus one info.json:
  - the versioned name (example: `ionos-essentials-0.1.1-php7.4.zip`)
  - the `latest` name (example: `ionos-essentials-latest-php7.4.zip`)
  - the legacy alias (example: `ionos-essentials.latest.zip`)
  - `<plugin>-info.json`

- **Two `info.json` flavours**: the GitHub release `@ionos-wordpress/latest` and the S3 folder each
  get their own `<plugin>-info.json`. Both share the same `version`, `slug`, `last_updated` and
  changelog - they differ only in their `package` field: the GitHub flavour points at the GitHub
  release download URL, the S3 flavour at the S3 copy of the same zip. This is what lets an
  installation that resolved its update descriptor from S3 also download the zip from S3, and one
  that resolved from GitHub stay entirely on GitHub.

## S3-first, GitHub-fallback resolution (transition period)

Released plugins query S3 first and fall back to GitHub only when S3 is unreachable, answers with a
non-200 status, or returns a body that is not valid JSON. A valid S3 `info.json` always wins - there
is no version comparison between the two sources.

The GitHub URL is not going away yet: an installation that has not received an update since the
switch to S3 still carries the pre-migration state (the old `Update URI` header value for
`wp-plugin` packages, or the pre-S3 hardcoded GitHub-only URL for `ionos-core`) and would stop
receiving updates if GitHub disappeared before that installation catches up. The GitHub fallback
constant can be removed once no installation in the field is still in that pre-migration state.

## Test-phase releases in a fork

Build time and release time must agree on `S3_FOLDER`, because the folder is baked into the plugin
artifact (`Update URI` header / update-checker URL) during the pre-release workflow, while the S3
upload happens in the release workflow.

This cannot be exercised in the main repository:

- `pre-release.yml` triggers on `push` to `main` and has no `workflow_dispatch`, so there is no
  per-run switch for the folder.
- The zips uploaded to S3 are the exact same artifacts attached to the GitHub release
  `@ionos-wordpress/latest`. A test-phase release in the main repository would ship plugins
  pointing at the `test` folder to real users.

Instead, run it in a fork (see [Forking](./6-forking.md)):

1. Set the `S3_FOLDER` repository variable to `test` in the fork's GitHub Actions settings
   (_Settings > Secrets and variables > Actions > Variables_) - both `pre-release.yml` and
   `release.yaml` export it, and CI never sees an uncommitted `.env.local`.
2. Add the `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` repository secrets, otherwise every S3
   upload is skipped with an error.
3. Push `develop` to `main` in the fork to create the pre-release(s), then trigger the `release`
   workflow manually to promote them and mirror the assets to `s3://web-hosting/test/`.
4. Verify the result without credentials, since the bucket is publicly readable, e.g.:

   ```
   curl -sI https://s3-de-central.profitbricks.com/web-hosting/test/ionos-essentials-latest-php7.4.zip
   curl -s  https://s3-de-central.profitbricks.com/web-hosting/test/ionos-essentials-info.json | jq .
   ```

For local, single-machine runs, setting `S3_FOLDER=test` in the (gitignored) `.env.local` is
enough, since it only affects your own `pnpm release`/`pnpm pre-release` runs, never CI.

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
