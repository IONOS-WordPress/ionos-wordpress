#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# the workflow in detail:
# - discover ALL releases flagged "pre-release" (any number of non-private packages can have one
#   at once) and sanity-check they all point at the same commit, i.e. came from one
#   pre-release.sh run - abort with a clear error otherwise
# - check if a 'latest' release exists
#   - if not, create a 'latest' release
# - loop over every discovered 'pre-release' and, for each one, take over its assets into the
#   'latest' release
#   - semantic versions in assets will be renamed to 'latest'
#       (example: ionos-essentials-0.1.1-php7.4.zip => ionos-essentials-latest-php7.4.zip)
#   - a info.json file will be created/updated for each plugin asset (ionos-essentials-0.1.1-php7.4.zip => ionos-essentials-info.json)
#       containing { version, slug, package, sections: { changelog } }, where package points to the download url
#       of the 'latest' flagged release (example: https://.../ionos-essentials-0.1.1-php7.4.zip)
#   - every asset and a second, s3 flavoured info.json are mirrored to the s3 folder $S3_FOLDER
#       (see .env). the s3 info.json points at the s3 copy of the zip instead of the github one,
#       so plugins resolving their update from s3 also download from s3
#   - remove the 'pre-release' flag from that release, individually, once its assets are processed
# - after the loop, update the 'latest' release's notes once with a combined list of every
#   package promoted this run
#
# afterwards the 'latest' release will contain the same assets as every processed 'pre-release'
# except that semantic version numbers in asset filenames are replaced with 'latest'; assets of
# packages not part of this run are left untouched (the 'latest' release accumulates assets from
# every package ever published, keyed by filename)
#
# the s3 folder accumulates the same way and ends up holding, per released zip:
# - the versioned name         (example: ionos-essentials-0.1.1-php7.4.zip)
# - the 'latest' name          (example: ionos-essentials-latest-php7.4.zip)
# - the legacy name            (example: ionos-essentials.latest.zip)
# plus one <plugin>-info.json per package (example: ionos-essentials-info.json)
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/_bootstrap.sh"
set -x
# ensure we have a GITHUB_TOKEN
if [[ -z "${GITHUB_TOKEN}" ]]; then
  ionos.wordpress.log_error "GITHUB_TOKEN environment variable is not set."
  exit 1
fi

# set GH_TOKEN to GITHUB_TOKEN if not set
# this is needed for gh cli to work
export GH_TOKEN=${GH_TOKEN:-$GITHUB_TOKEN}

readonly LATEST_RELEASE_TAG="@ionos-wordpress/latest"

# example value : IONOS-WordPress/ionos-wordpress
readonly GITHUB_OWNER_REPO=$(git remote get-url origin | sed -E 's|.*[:/]([^/]+)/([^/.]+)(\.git)?$|\1/\2|')

# s3 mirror of the release assets. bucket and endpoint are fixed, the folder is configurable via
# `.env`/`.env.local` so a fork can publish a test release without writing into the production
# folder (see docs/7-release.md)
readonly S3_ENDPOINT='https://s3-de-central.profitbricks.com'
readonly S3_BUCKET='web-hosting'
readonly S3_PRODUCTION_FOLDER='ionos-group'
readonly UPSTREAM_OWNER_REPO='IONOS-WordPress/ionos-wordpress'

# guard against an empty folder - that would scatter the release assets across the bucket root
if [[ -z "${S3_FOLDER}" ]]; then
  ionos.wordpress.log_error "S3_FOLDER environment variable is not set."
  exit 1
fi

# $S3_FOLDER is interpolated into an unquoted sed replacement and an unquoted heredoc passed to
# the aws-cli docker container (see ionos.wordpress.s3_upload below), and the baked-folder parser
# above already assumes [A-Za-z0-9_.-]+ - restrict it to that same alphabet so a folder containing
# '&', shell metacharacters, whitespace, or embedded CR/LF can't produce a mismatched URL or
# execute unintended commands in the container
if [[ ! "$S3_FOLDER" =~ ^[A-Za-z0-9_.-]+$ ]]; then
  ionos.wordpress.log_error "S3_FOLDER='$S3_FOLDER' contains characters outside the supported [A-Za-z0-9_.-]+ alphabet."
  exit 1
fi

# tie the s3 folder to the repository the release is cut from. the released zips are the very same
# artifacts that get attached to the github release, and they carry the s3 folder baked into their
# 'Update URI' header - so publishing with a mismatched folder either ships test artifacts to real
# users or lets a fork overwrite production assets. both directions abort instead
# GitHub owner/repo names are case-insensitive - a clone URL cased differently from
# $UPSTREAM_OWNER_REPO (e.g. an all-lowercase remote) must still be recognized as upstream
if [[ "${GITHUB_OWNER_REPO,,}" == "${UPSTREAM_OWNER_REPO,,}" && "$S3_FOLDER" != "$S3_PRODUCTION_FOLDER" ]]; then
  ionos.wordpress.log_error "refusing to release from '$UPSTREAM_OWNER_REPO' with S3_FOLDER='$S3_FOLDER' - the production release must publish to '$S3_PRODUCTION_FOLDER'. Unset the override (see .env.local) or run this from a fork."
  exit 1
fi

if [[ "${GITHUB_OWNER_REPO,,}" != "${UPSTREAM_OWNER_REPO,,}" && "$S3_FOLDER" == "$S3_PRODUCTION_FOLDER" ]]; then
  ionos.wordpress.log_error "refusing to release from the fork '$GITHUB_OWNER_REPO' into the production folder '$S3_PRODUCTION_FOLDER'. Set S3_FOLDER to a test folder in the fork's .env so its CI sees it too (see docs/7-release.md)."
  exit 1
fi

readonly S3_BASE_URL="$S3_ENDPOINT/$S3_BUCKET/$S3_FOLDER"

# copy $1 into the release s3 folder as object $2. a missing aws secret degrades the release
# (github assets are still promoted) instead of aborting it, matching the previous behavior
function ionos.wordpress.s3_upload() {
  local FILE="$1"
  local OBJECT_NAME="$2"

  if [[ -z "${AWS_ACCESS_KEY_ID}" ]] || [[ -z "${AWS_SECRET_ACCESS_KEY}" ]]; then
    ionos.wordpress.log_error "skip s3 upload of '$OBJECT_NAME' - AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY are required"
    return 1
  fi

  echo "upload '$FILE' to s3 as '$S3_FOLDER/$OBJECT_NAME'"

  if ! docker run -i --rm -v "$(realpath "$FILE")":"/tmp/$OBJECT_NAME" --entrypoint bash amazon/aws-cli - <<EOF
    export AWS_REQUEST_CHECKSUM_CALCULATION=when_required
    export AWS_RESPONSE_CHECKSUM_VALIDATION=when_required

    aws configure set aws_access_key_id "$AWS_ACCESS_KEY_ID"
    aws configure set aws_secret_access_key "$AWS_SECRET_ACCESS_KEY"

    aws --endpoint-url $S3_ENDPOINT s3 cp /tmp/$OBJECT_NAME s3://$S3_BUCKET/$S3_FOLDER/$OBJECT_NAME
EOF
  then
    local error_message="Failed to upload '$OBJECT_NAME' to s3"
    [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
    echo "Error: $error_message"
    return 1
  fi
}

# do explicitly ONLY when running locally (=> not in CI)
if [[ "${CI}" == '' ]]; then
  # echo "${GH_TOKEN}" | pnpm gh auth login --with-token
  # in case the repo is forked we need to explicitly set the default repo
  pnpm gh repo set-default $(git remote get-url origin | sed -E 's/.*[:\/]([^\/]+\/[^\/]+)\.git/\1/')
fi

# get all pre-release flagged releases (explicit --limit since `gh release list` defaults to 30)
# use `tagName`, not `name` - `name` is the release title, which can differ from the tag (e.g. for
# the root package, see pre-release.sh) and is what git/gh identify releases by everywhere below
mapfile -t PRE_RELEASES < <(gh release list --json tagName,isPrerelease --limit 1000 | jq -r '.[] | select(.isPrerelease == true) | .tagName')

if [[ ${#PRE_RELEASES[@]} -eq 0 ]]; then
  ionos.wordpress.log_warn "Nothing to release - no release flagged as 'pre-release' found."
  exit 0
fi

ionos.wordpress.log_header "Releasing ${#PRE_RELEASES[@]} package(s): ${PRE_RELEASES[*]}"

# sanity check: all discovered prereleases must point at the same commit, i.e. come from one
# `pre-release.sh` run. if they don't, abort instead of silently mis-promoting a partial/stale mix.
readonly PRE_RELEASE_COMMIT_HASH=$(git rev-list -n 1 "${PRE_RELEASES[0]}")
STALE_PRE_RELEASES=()
for PRE_RELEASE in "${PRE_RELEASES[@]}"; do
  COMMIT_HASH=$(git rev-list -n 1 "$PRE_RELEASE")
  [[ "$COMMIT_HASH" != "$PRE_RELEASE_COMMIT_HASH" ]] && STALE_PRE_RELEASES+=("$PRE_RELEASE")
done

if [[ ${#STALE_PRE_RELEASES[@]} -gt 0 ]]; then
  DELETE_COMMANDS=$(printf 'gh release delete "%s" --yes\n' "${STALE_PRE_RELEASES[@]}")
  error_message="skip releasing - discovered prereleases don't share one commit (expected all at $PRE_RELEASE_COMMIT_HASH). Stale release(s): ${STALE_PRE_RELEASES[*]}. See docs/7-release.md for manual fixup instructions. Run the following to remove the stale release(s), then re-run this script:
$DELETE_COMMANDS"
  [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
  ionos.wordpress.log_error "$error_message"
  exit 1
fi

# ensure release titled $LATEST_RELEASE_TAG exists
if ! gh release view "$LATEST_RELEASE_TAG"; then
  ionos.wordpress.log_info "did not found a release named/tagged '$LATEST_RELEASE_TAG'"

  # ensure there is no tag named "$LATEST_RELEASE_TAG"
  git tag -d "$LATEST_RELEASE_TAG" ||:
  git push origin --delete "$LATEST_RELEASE_TAG" ||:

  # create release
  gh release create "$LATEST_RELEASE_TAG" \
    --notes '' \
    --title "$LATEST_RELEASE_TAG" \
    --latest=false

  echo "created release '$LATEST_RELEASE_TAG'"
fi

# one bullet line per processed package, collected during the loop below and used for the
# combined 'latest' release notes once all prereleases have been processed
RELEASE_NOTES_LINES=()

for PRE_RELEASE in "${PRE_RELEASES[@]}"; do
  ionos.wordpress.log_header "Processing pre-release '$PRE_RELEASE'"

  PRE_RELEASE_URL="https://github.com/$GITHUB_OWNER_REPO/releases/tag/$(printf '%s' "$PRE_RELEASE" | jq -Rrs '@uri')"
  RELEASE_NOTES_LINES+=("* [$PRE_RELEASE]($PRE_RELEASE_URL)")

  # $PRE_RELEASE is always "<package-name>@<version>" (see pre-release.sh) - strip the trailing
  # "@<version>" to recover the package name, then resolve it to its workspace folder name via
  # pnpm instead of guessing from asset filenames (which could be confused by a plugin name that
  # itself contains a version-like substring)
  PACKAGE_NAME="${PRE_RELEASE%@*}"
  PLUGIN=$(pnpm ls --filter "$PACKAGE_NAME" --json --depth -1 | jq -r '.[0].path' | xargs basename)

  # update latest release assets
  ASSETS=$(gh release view $PRE_RELEASE --json assets --jq '.assets[] | .name')
  for ASSET in $ASSETS; do
    TARGET_ASSET_FILENAME=$(echo $ASSET | sed -E 's/[0-9]+\.[0-9]+\.[0-9]+/latest/g')
    rm -f $TARGET_ASSET_FILENAME
    echo "upload release '$PRE_RELEASE' asset '$ASSET' as '$TARGET_ASSET_FILENAME' to release '$LATEST_RELEASE_TAG'"
    gh release download $PRE_RELEASE --pattern $ASSET -O $TARGET_ASSET_FILENAME

    # the guard above only compares the current repo/S3_FOLDER pair, it can't see what folder was
    # actually baked into this asset's 'Update URI' header (see the '__S3_FOLDER__' substitution
    # in build.sh) when it was built by pre-release.sh, possibly in a different environment/run.
    # promoting it under a mismatched $S3_FOLDER would publish a zip whose own header points
    # somewhere else, leaving installations resolving from that s3 folder unable to find updates
    #
    # match this package's own '<plugin>-info.json' url specifically rather than any
    # 'web-hosting/<folder>/' url in the archive - a package can bundle unrelated s3 urls (e.g.
    # ionos-core's marketplace/config.php) that would otherwise be matched first. '|| true' keeps
    # a zip with no match from aborting the script under 'set -eo pipefail'
    BAKED_S3_FOLDER=$(unzip -p "$TARGET_ASSET_FILENAME" 2>/dev/null | grep -oE "$S3_BUCKET/[A-Za-z0-9_.-]+/${PLUGIN}-info\.json" | head -1 | cut -d/ -f2 || true)
    if [[ -n "$BAKED_S3_FOLDER" && "$BAKED_S3_FOLDER" != "$S3_FOLDER" ]]; then
      error_message="refusing to promote asset '$ASSET' of pre-release '$PRE_RELEASE' - it was built with S3_FOLDER='$BAKED_S3_FOLDER' baked into its 'Update URI' header, but this run is promoting to S3_FOLDER='$S3_FOLDER'. Re-run pre-release.sh with S3_FOLDER='$S3_FOLDER' before promoting, or promote from an environment whose S3_FOLDER matches the artifact."
      [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
      ionos.wordpress.log_error "$error_message"
      exit 1
    fi

    if ! gh release upload $LATEST_RELEASE_TAG $TARGET_ASSET_FILENAME --clobber; then
      error_message="Failed to upload asset $TARGET_ASSET_FILENAME"
      [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
      echo "Error: $error_message"
    fi
    # mirror the asset to s3 under every name it is known by : the versioned name it has in the
    # pre-release, the 'latest' name it has in $LATEST_RELEASE_TAG, and the legacy
    # '<plugin>.latest.zip' name kept for consumers that were built against it. all three are the
    # same bytes, so the downloaded file is uploaded three times instead of downloaded three times
    S3_LEGACY_FILENAME=$(echo $TARGET_ASSET_FILENAME | sed -E 's/-latest-.+$/.latest.zip/')

    # tracked so the s3-flavoured info.json below is skipped if any package upload failed -
    # otherwise it would advertise a 'package' url for an object that was never actually
    # written to s3
    S3_ASSET_UPLOAD_OK=1
    ionos.wordpress.s3_upload "$TARGET_ASSET_FILENAME" "$ASSET" || S3_ASSET_UPLOAD_OK=0
    ionos.wordpress.s3_upload "$TARGET_ASSET_FILENAME" "$TARGET_ASSET_FILENAME" || S3_ASSET_UPLOAD_OK=0
    if [[ "$S3_LEGACY_FILENAME" != "$TARGET_ASSET_FILENAME" ]]; then
      ionos.wordpress.s3_upload "$TARGET_ASSET_FILENAME" "$S3_LEGACY_FILENAME" || S3_ASSET_UPLOAD_OK=0
    fi

    rm -f $TARGET_ASSET_FILENAME

    #
    # create/update <plugin>-latest.json file (example : ionos-essentials.info.json)
    #
    {
      # example: 1.2.3
      VERSION=$(echo $ASSET | sed -E 's/.*-([0-9]+\.[0-9]+\.[0-9]+)-.*/\1/')
      # example : ionos-essentials/ionos-essentials.php
      SLUG="${PLUGIN}/${PLUGIN}.php"
      # example: https://github.com/lgersman/ionos-wordpress/releases/download/%40ionos-wordpress%2Fessentials%400.1.3/ionos-essentials-0.1.3-php7.4.zip
      GITHUB_PACKAGE_URL="https://github.com/$GITHUB_OWNER_REPO/releases/download/$(printf $PRE_RELEASE | jq -Rrs '@uri')/$ASSET"
      # example: https://s3-de-central.profitbricks.com/web-hosting/ionos-group/ionos-essentials-0.1.3-php7.4.zip
      S3_PACKAGE_URL="$S3_BASE_URL/$ASSET"

      LAST_UPDATED=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
      # CHANGELOG is the release note of the pre-release (aka the changelog markdown of the release)
      CHANGELOG="$(gh release view $PRE_RELEASE --json body --jq '.body')"

      # Convert markdown in CHANGELOG to HTML using a Node.js package
      CHANGELOG_HTML=$(echo "$CHANGELOG" | npx marked)

      INFO_JSON_FILENAME="${PLUGIN}-info.json"

      # the github and the s3 flavour of the info.json differ in their 'package' download url
      # only - each flavour has to point at the plugin zip hosted next to it, so an installation
      # that resolved its update descriptor from s3 also downloads the zip from s3
      INFO_JSON_FILTER='{version: $version, slug: $slug, package: $package, last_updated: $last_updated, requires_wp: "6.0", sections : { changelog: $changelog }}'
      INFO_JSON_ARGS=(
        --arg version "$VERSION"
        --arg slug "$SLUG"
        --arg last_updated "$LAST_UPDATED"
        --arg changelog "$CHANGELOG_HTML"
      )

      jq -n "${INFO_JSON_ARGS[@]}" --arg package "$GITHUB_PACKAGE_URL" "$INFO_JSON_FILTER" > "$INFO_JSON_FILENAME"

      if ! gh release upload $LATEST_RELEASE_TAG $INFO_JSON_FILENAME --clobber; then
        error_message="Failed to upload asset $INFO_JSON_FILENAME"
        [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
        echo "Error: $error_message"
      fi

      if [[ "$S3_ASSET_UPLOAD_OK" == "1" ]]; then
        jq -n "${INFO_JSON_ARGS[@]}" --arg package "$S3_PACKAGE_URL" "$INFO_JSON_FILTER" > "$INFO_JSON_FILENAME"

        # a failed upload here would leave the previous $INFO_JSON_FILENAME object in s3 intact -
        # s3-first clients would keep seeing that stale-but-valid descriptor and never learn a new
        # version exists (they never reach the github fallback since s3 answered), so abort the
        # whole release instead of promoting with it left in place
        if ! ionos.wordpress.s3_upload "$INFO_JSON_FILENAME" "$INFO_JSON_FILENAME"; then
          error_message="Failed to upload the s3 flavoured $INFO_JSON_FILENAME - aborting to avoid leaving the previous, stale descriptor in place"
          [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
          ionos.wordpress.log_error "$error_message"
          exit 1
        fi
      else
        # skipping the s3 flavoured descriptor here would leave the previous $INFO_JSON_FILENAME
        # object in s3 intact and still valid, so s3-first clients would keep seeing it and never
        # reach the github fallback - and the pre-release flag removal below would then make this
        # release unretriable. abort instead, matching the upload-failure case above, so the
        # pre-release flag stays set and a re-run can retry the mirror
        error_message="aborting - one or more package uploads to s3 for asset '$ASSET' failed, so the s3 flavoured $INFO_JSON_FILENAME would either be skipped (leaving a stale descriptor in place) or point at an object that was never written"
        [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
        ionos.wordpress.log_error "$error_message"
        exit 1
      fi

      rm -f $INFO_JSON_FILENAME
    }
  done

  # remove the 'pre-release' flag from this PRE_RELEASE - each processed release is individually
  # flipped to non-prerelease. --latest=false: GitHub's 'latest' flag is a repo-wide singleton, so
  # with N packages promoted in one run only the floating $LATEST_RELEASE_TAG release below should
  # carry it - flagging a per-package release here would arbitrarily pick whichever package
  # happens to be processed last in this loop
  gh release edit "$PRE_RELEASE" --prerelease=false --draft=false --latest=false

  ionos.wordpress.log_info "Removed 'pre-release' flag from release '$PRE_RELEASE'"
done

# update 'latest' release data with the combined notes for every package processed this run
RELEASE_NOTES=$(printf '%s\n' "${RELEASE_NOTES_LINES[@]}")

# $LATEST_RELEASE_TAG is a floating release that gets edited on every run, but GitHub only shows
# the timestamp of when a release was first published ('x days/months ago') and there's no API
# field to set it directly - flipping draft=true then draft=false forces GitHub to re-stamp
# published_at to now, so the displayed date reflects the latest promotion instead of the
# release's original creation
gh release edit "$LATEST_RELEASE_TAG" --draft=true
gh release edit "$LATEST_RELEASE_TAG" \
  --title "$LATEST_RELEASE_TAG" \
  --target $PRE_RELEASE_COMMIT_HASH \
  --notes "latest release contains:
$RELEASE_NOTES" \
  --tag $LATEST_RELEASE_TAG \
  --latest=true \
  --draft=false \
  --prerelease=false

readonly success_message="Successfully updated release '$LATEST_RELEASE_TAG' (https://github.com/$GITHUB_OWNER_REPO/releases/tag/%40ionos-wordpress%2Flatest) with packages:
$RELEASE_NOTES"
# @TODO: success message can be markdown containing links
[[ "${CI:-}" == "true" ]] && echo "$success_message" >> $GITHUB_STEP_SUMMARY
echo "$success_message"

# notify release to google chat room
if [[ "${GCHAT_RELEASE_ANNOUNCEMENTS_WEBHOOK}" != '' ]]; then
  # use the triggering actor of the github event if available, otherwise use the git config user.name
  TRIGGERING_ACTOR="${GITHUB_TRIGGERING_ACTOR:-$(git config user.name)}"
  # use the repository name from the github event if available, otherwise use the repository name from the git config
  REPOSITORY_NAME=$( [[ $GITHUB_EVENT_PATH != '' ]] && jq -r '.repository.name' $GITHUB_EVENT_PATH || basename $(realpath .))
  # use the repository url from the github event if available, otherwise use the repository url from the git config
  REPOSITORY_URL=$( [[ $GITHUB_EVENT_PATH != '' ]] && echo "$(jq -r '.repository.html_url' $GITHUB_EVENT_PATH)/releases" || git remote get-url --push origin)
  curl -X POST \
    -H 'Content-Type: application/json' \
    -d "{\"text\": \"*${TRIGGERING_ACTOR}* created a new release from repository *${REPOSITORY_NAME}*.\n\n$success_message\n\nSee ${REPOSITORY_URL}\"}" \
    "${GCHAT_RELEASE_ANNOUNCEMENTS_WEBHOOK}"
else
  if [[ "${CI:-}" == "true" ]]; then
    echo "::warning::skip sending google chat release announcement message : secret GCHAT_RELEASE_ANNOUNCEMENTS_WEBHOOK is not defined"
  else
    ionos.wordpress.log_warn "CI environment detected - skip setting up git hooks"
  fi
fi
