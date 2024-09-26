---
"ionos-wordpress": patch
---

changeset integration

  - detailed workflow (local first) :

  ```
  ...
  # add as many changesets as you want while developing on a release
  pnpm changeset add
  ...

  ...
  # some days/weeks/months later ...
  #
  # update semver versions and generate changelogs using changeset
  # all changeset files will be merged into the referenced monorepo packages and will be removed from the changeset directory
  pnpm changeset version

  # update pnpm lock file
  pnpm install (to update lock file)

  # commit updated files
  git add .
  git commit -am "chore(release) : $(jq -r '.version | values' package.json) [skip release]"

  # tag release
  pnpm changeset tag

  # push changes and tags
  git push && git push --tags

  # merge develop into main
  git push origin develop:main
  ```
