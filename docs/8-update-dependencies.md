# update-dependencies

`pnpm update-dependencies` will scan all monorepo dependencies for updates.

It does much more than dependabot. Dependabot will only scan for npm and composer updates.

`pnpm update-dependencies` will additionally scan for updates of :

- NodeJS
- pnpm
- Docker
- PHP versionn

Running `pnpm update-dependencies` will update npm dependencies automatically.

All other dependency updates will be spit out to console and must be applied manually.
Follow the console output to do all manually steps.

> If `pnpm update-dependencies` lists updates of `pnpm` or PHP versions you should really be careful test if everything runs fine afterwards.
> If you are unsure wwhat to do - leave the current `pnpm` and PHP versions as is.

> To update the node dependencies including minor and major changes you can start the update-dependencies script with an additional switch : `pnpm update-dependencies --pnpm-opts "--latest"`

After finishing all update steps you need to

- increment manually the version property of the `package.json` files of each workspace package containing composer.json dependencies or python dependencies.

- call `pnpm build` to get the `composer.lock` files updated

- call `pnpm test` to check if the updates did not broke something

- in case of tool updates (like `prettier` or `eslint`) you should also try if the tool already works.

After all you can create a PR of all changes.
