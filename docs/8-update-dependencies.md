# update-dependencies

`pnpm update-dependencies` scans all monorepo dependencies for updates.

It does much more than Dependabot. Dependabot only scans for npm and Composer updates.

`pnpm update-dependencies` also scans for updates of:

- NodeJS
- pnpm
- Docker
- PHP version

Running `pnpm update-dependencies` updates npm dependencies automatically.

The command prints all other dependency updates to the console. You must apply these manually.
Follow the console output to complete all manual steps.

> If `pnpm update-dependencies` lists updates of `pnpm` or PHP versions, be careful. Test that everything still runs fine afterward.
> If you are unsure what to do, leave the current `pnpm` and PHP versions as they are.

> To update the node dependencies, including minor and major changes, start the update-dependencies script with an extra switch: `pnpm update-dependencies --pnpm-opts "--latest"`

After you finish all update steps, do the following:

- Manually increment the version property in the `package.json` file of each workspace package that contains composer.json dependencies or Python dependencies.

- Run `pnpm build` to update the `composer.lock` files.

- Run `pnpm test` to check that the updates did not break anything.

- For tool updates (such as `prettier` or `eslint`), also test that the tool still works.

After you complete these steps, create a PR of all changes.
