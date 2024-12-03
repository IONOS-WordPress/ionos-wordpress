#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to create a github release
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# abort if we are not on the "main" branch
if [[ "$(git rev-parse --abbrev-ref HEAD)" != 'main' ]]; then
  ionos.wordpress.log_error "You can only release from the main branch"
  exit 1
fi

# abort if the working directory is not clean
if [[ -n "$(git status --porcelain)" ]]; then
  ionos.wordpress.log_error "You have uncommitted changes. Please commit or stash them before releasing."
  exit 1
fi

# ensure ./tmp/release is a fresh empty directory
rm -rf ./tmp/release
mkdir -p ./tmp/release

# abort if no changesets proposing version changes were found
# changeset does not support --format option as of now so we need to use the --output option
# see https://github.com/changesets/changesets/issues/1020
# fetch changeset status into ./tmp/release/status.json
pnpm changeset status --verbose --output ./tmp/release/status.json
# count changesets
CHANGESETS_COUNT=$(jq '.changesets // [] | length' ./tmp/release/status.json)
if [[ $CHANGESETS_COUNT -eq 0 ]]; then
  ionos.wordpress.log_warn "Nothing to release - no changesets found."
  exit 0
fi

# update versions and create changelog files from changesets
pnpm changeset version

# update pnpm-lock.yaml
pnpm install

# build repository
pnpm build

# add updated files to git
git add .

# set git user to the user who made the last commit
# (aka the user who triggered the release)
git config user.name "$(git --no-pager log --format=format:'%an' -n 1)"
git config user.email "$(git --no-pager log --format=format:'%ae' -n 1)"

# commit changes
git commit -am "chore(release) : updated versions [skip release]"

# tag release
pnpm changeset tag

# push changes and tags
git push && git push --tags

# ensure ./tmp/release is a fresh empty directory
rm -rf ./tmp/release
mkdir -p ./tmp/release

# loop over all package.json files changed by changeset version command
for PACKAGE_JSON in $(git --no-pager diff --name-only HEAD HEAD~1 | grep 'package.json'); do
  PACKAGE_VERSION=$(jq -r '.version' $PACKAGE_JSON)
  PACKAGE_NAME=$(jq -r '.name' $PACKAGE_JSON)
  PACKAGE_PATH=$(dirname $PACKAGE_JSON)

  # skip packages with private flag
  if [[ "$(jq -r '.private // false' $PACKAGE_JSON)" == "true" ]]; then
    ionos.wordpress.log_warn "skipping release package $PACKAGE_NAME - it is marked as private"
    continue
  fi

  ionos.wordpress.log_header "creating release for package $PACKAGE_NAME (version=$PACKAGE_VERSION)"

  PACKAGE_RELEASENOTES_FILE="./tmp/release/${PACKAGE_NAME//\//-}.md"
  # write changes extracted from changelog file from last commit
  # and write it to a file
  git --no-pager diff --unified=0 HEAD~1 HEAD $PACKAGE_PATH/CHANGELOG.md | grep --color=never -P '(?<=^\+)(?!\+\+).*' | sed 's/^+//' > "$PACKAGE_RELEASENOTES_FILE"

  PACKAGE_ARTIFACTS=()
  # PACKAGE_FLAVOUR is the name of the parent directory of the package (i.e. wp-plugin|npm|docker|...)
  PACKAGE_FLAVOUR=$(basename $(dirname $PACKAGE_PATH))
done

if [[ "$PACKAGE_FLAVOUR" == "." ]]; then
  # if root package is being released, collect all artifacts
  ARTIFACTS=($(find ./packages -mindepth 4 -maxdepth 4 -type f -name '*.zip ' -or -name "*.tgz"))
else
  case "$PACKAGE_FLAVOUR" in
    docker)
      ionos.wordpress.log_warn "skipping $PACKAGE_FLAVOUR package $PACKAGE_NAME - docker packages are not distributable"
      ;;
    npm)
      ARTIFACTS+=("$(find $PACKAGE_PATH/dist -type f -name '*.tgz')")
      ;;
    wp-plugin|wp-theme)
      ARTIFACTS+=("$(find $PACKAGE_PATH/dist -type f -name '*.zip')")
      ;;
    *)
      ionos.wordpress.log_error "don't know how to handle workspace package flavor '$PACKAGE_FLAVOUR' (extracted from path=$PACKAGE_PATH)"
      exit 1
      ;;
  esac
fi

RELEASE_TITLE=$([[ "$PACKAGE_FLAVOUR" == "." ]] && echo "$PACKAGE_VERSION" || echo "$PACKAGE_NAME@$PACKAGE_VERSION")
gh release create "$PACKAGE_NAME@$PACKAGE_VERSION" "${ARTIFACTS[@]}" --title "$RELEASE_TITLE" --notes-file "$PACKAGE_RELEASENOTES_FILE"

# merge changes back to develop branch
git checkout develop
git merge main
git push -u origin develop


