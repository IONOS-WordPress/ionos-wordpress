#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to create a github release marked as pre-release.
# only packages not marked as private will be released
#
# workflow:
# - features (including changesets) will be developed on feature branches and merged into the develop branch
# - releasing means merging the develop branch into the main branch. the github workflow will automatically create the release and the artifacts
# - after creating the releases the release changes will be merged back into the maiin and develop branch
#
# the script will abort
# - if the current branch is not the main branch
# - if the working directory is not clean
# - (local) if the GITHUB_TOKEN environment variable is not set
# - if no changesets proposing version changes were found
#
# local usage:
# - ensure branches develop and main are up to date : `git fetch -all`
# - switch to local develop branch and ensure it's clean (i.e no uncommit changes): `git switch develop && git status`
# - go to branch main and pull changes from local develop branch : `git switch main && git pull . develop`
# - execute the release script : `pnpm release`
#
# remote usage:
# - switch to develop branch: `git switch develop`
# - ensure remote branches develop and main are up to date : `git push origin develop && git push origin main`
# - push changes on develop branch to remote branch main : `git push origin develop:main`
# - wait for the github workflow to finish
# - merge changes back to develop branch : `git switch develop && git merge main && git push origin develop`


# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# abort if we are not on the "main" branch
if [[ "$(git rev-parse --abbrev-ref HEAD)" != 'main' ]]; then
  ionos.wordpress.log_error "You can only release from the branch 'main'. Current branch is '$(git rev-parse --abbrev-ref HEAD)'."
  exit 1
fi

# abort if the working directory is not clean
if [[ -n "$(git status --porcelain)" ]]; then
  ionos.wordpress.log_error "You have uncommitted changes. Please commit or stash them before releasing."
  exit 1
fi

# ensure we have a GITHUB_TOKEN
if [[ -z "${GITHUB_TOKEN}" ]]; then
  ionos.wordpress.log_error "GITHUB_TOKEN environment variable is not set. Please set it before releasing."
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
# read ./tmp/release/status.json into variable CHANGESET_STATUS_JSON
CHANGESET_STATUS_JSON=$(cat ./tmp/release/status.json)
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

# build & test repository
pnpm test

# generate sbom file
docker run -it --rm -v $(pwd):/project anchore/syft \
  scan /project \
  --source-name ionos-wordpress \
  --select-catalogers "+javascript-package-cataloger,+github-actions-usage-cataloger,+php-composer-lock-cataloger" \
  -o spdx-json=/project/ionos-wordpress.sbom.json.tmp && \
  jq '.' ionos-wordpress.sbom.json.tmp > ionos-wordpress.sbom.syft.json && rm -f ionos-wordpress.sbom.json.tmp

# add updated files to git
git add -A .

# set git user to the user who made the last commit
# (aka the user who triggered the release)
# git config user.name "$(git --no-pager log --format=format:'%an' -n 1)"
# git config user.email "$(git --no-pager log --format=format:'%ae' -n 1)"
# see https://github.com/actions/checkout/pull/1184#issue-1595060720
git config user.name "github-actions[bot]"
git config user.email "41898282+github-actions[bot]@users.noreply.github.com"

# commit changes
# no-verify will disable the pre-push hook since we wont automatically run lint
git commit --no-verify -am "chore(release) : updated versions and sbom information [skip release]"

# tag release
pnpm changeset tag

# push changes and tags
git push && git push --tags

# ensure ./tmp/release is a fresh empty directory
rm -rf ./tmp/release
mkdir -p ./tmp/release

# set GH_TOKEN to GITHUB_TOKEN if not set
# this is needed for gh cli to work
export GH_TOKEN=${GH_TOKEN:-$GITHUB_TOKEN}

# do explicitly ONLY when running locally (=> not in CI)
if [[ "${CI}" == '' ]]; then
  # echo "${GH_TOKEN}" | pnpm gh auth login --with-token
  # in case the repo is forked we need to explicitly set the default repo
  pnpm gh repo set-default $(git remote get-url origin | sed -E 's/.*[:\/]([^\/]+\/[^\/]+)\.git/\1/')
fi

# workaround : delete all existing prereleases (multiple releases can have the prerelease tag through canceled/broken releases)
# this is for the case that existing releases are marked as prerelease - we want to ensure that only one release has the prerelease tag
gh release list --json name,isPrerelease | jq -r '.[] | select(.isPrerelease == true) | .name' | xargs -I {} gh release delete --yes {}

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

  if [[ ${#ARTIFACTS[@]} -eq 0 ]]; then
    ionos.wordpress.log_warn "no artifacts found for package $PACKAGE_NAME"
    continue
  fi
  pnpm gh release create "$PACKAGE_NAME@$PACKAGE_VERSION" "${ARTIFACTS[@]}" --prerelease --title "$RELEASE_TITLE" --notes-file "$PACKAGE_RELEASENOTES_FILE"
done

# merge changes back to develop branch
git checkout develop
# pull changes from main branch instead of merging to avoid merge commits
git pull . main
# push changes to remote develop branch
git push -u origin develop

# notify release to google chat room
if [[ "${GCHAT_RELEASE_ANNOUNCEMENTS_WEBHOOK}" != '' ]]; then
  # use the triggering actor of the github event if available, otherwise use the git config user.name
  TRIGGERING_ACTOR="${GITHUB_TRIGGERING_ACTOR:-$(git config user.name)}"
  # use the repository name from the github event if available, otherwise use the repository name from the git config
  REPOSITORY_NAME=$( [[ $GITHUB_EVENT_PATH != '' ]] && jq -r '.repository.name' $GITHUB_EVENT_PATH || basename $(realpath .))
  # use the repository url from the github event if available, otherwise use the repository url from the git config
  REPOSITORY_URL=$( [[ $GITHUB_EVENT_PATH != '' ]] && echo "$(jq -r '.repository.html_url' $GITHUB_EVENT_PATH)/releases" || git remote get-url --push origin)
  # changed packages computed by changeset
  CHANGED_PACKAGES=$(echo "$CHANGESET_STATUS_JSON" | jq -r '.releases[] | "* \(.name)(\(.oldVersion)->\(.newVersion))"')
  curl -X POST \
    -H 'Content-Type: application/json' \
    -d "{\"text\": \"*${TRIGGERING_ACTOR}* pre-released repository *${REPOSITORY_NAME}*.\nThe following packages would be pre-released:\n\n${CHANGED_PACKAGES} \n\nSee ${REPOSITORY_URL}\"}" \
    "${GCHAT_RELEASE_ANNOUNCEMENTS_WEBHOOK}"
else
  if [[ "${CI:-}" == "true" ]]; then
    echo "::warning::skip sending google chat release announcement message : secret GCHAT_RELEASE_ANNOUNCEMENTS_WEBHOOK is not defined"
  else
    ionos.wordpress.log_warn "CI environment detected - skip setting up git hooks"
  fi
fi
