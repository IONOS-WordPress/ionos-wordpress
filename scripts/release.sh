#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# the workflow in detail:
# - check if a release with flag "pre-release" exists
# - check if a 'latest' release exists
#   - if not, create a 'latest' release
# - take over commit hash, and assets from the 'pre-release' to the 'latest' release
#   - semantic versions in assets will be renamed to 'latest'
#       (example: ionos-essentials-0.1.1-php7.4.zip => ionos-essentials-latest-php7.4.zip)
#   - release note will be set to the 'pre-release' release url and title to make it easier to find the origin release
#   - a info.json file will be created/updated for each plugin asset (ionos-essentials-0.1.1-php7.4.zip => ionos-essentials-info.json)
#       containing { version, slug, package, sections: { changelog } }, where package points to the download url
#       of the 'latest' flagged release (example: https://.../ionos-essentials-0.1.1-php7.4.zip)
# - remove the 'pre-release' flag from the release used to populate the 'latest' release and flag it 'latest'
#
# afterwards the 'latest' release will contain the same assets as the 'pre-release' release
# except that semantic version numbers in asstes filenames are replaced with 'latest'
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# ensure we have a GITHUB_TOKEN
if [[ -z "${GITHUB_TOKEN}" ]]; then
  ionos.wordpress.log_error "GITHUB_TOKEN environment variable is not set."
  exit 1
fi

# set GH_TOKEN to GITHUB_TOKEN if not set
# this is needed for gh cli to work
export GH_TOKEN=${GH_TOKEN:-$GITHUB_TOKEN}

readonly LATEST_RELEASE_TAG="@ionos-wordpress/latest"

# do explicitly ONLY when running locally (=> not in CI)
if [[ "${CI}" == '' ]]; then
  # echo "${GH_TOKEN}" | pnpm gh auth login --with-token
  # in case the repo is forked we need to explicitly set the default repo
  pnpm gh repo set-default $(git remote get-url origin | sed -E 's/.*[:\/]([^\/]+\/[^\/]+)\.git/\1/')
fi

# get pre-release flagged release
PRE_RELEASE=$(gh release list --json name,isPrerelease | jq -r '.[] | select(.isPrerelease == true) | .name')
if [[ -z "$PRE_RELEASE" || $(echo "$PRE_RELEASE" | wc -l) -ne 1 ]]; then
  error_message="skip releasing - expected exactly one release flagged as 'pre-release' but found $([[ -z "$PRE_RELEASE" ]] && echo '0' || echo "$PRE_RELEASE" | wc -l)"
  [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
  ionos.wordpress.log_error "$error_message\n$PRE_RELEASE"
  exit 1
else
  PRE_RELEASE=$(echo "$PRE_RELEASE" | head -n 1)
  ionos.wordpress.log_header "Releasing $PRE_RELEASE"
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

# Get the commit hash of the tag associated with the pre-release
readonly PRE_RELEASE_COMMIT_HASH=$(git rev-list -n 1 "$PRE_RELEASE")

# example value : IONOS-WordPress/ionos-wordpress
readonly GITHUB_OWNER_REPO=$(git remote get-url origin | sed -E 's|.*[:/]([^/]+)/([^/.]+)(\.git)?$|\1/\2|')

# update 'latest' release data
readonly PRE_RELEASE_URL="https://github.com/$GITHUB_OWNER_REPO/releases/tag/$(printf $PRE_RELEASE | jq -Rrs '@uri')"
gh release edit "$LATEST_RELEASE_TAG" \
  --title "$LATEST_RELEASE_TAG" \
  --target $PRE_RELEASE_COMMIT_HASH \
  --notes "latest release is [$PRE_RELEASE]($PRE_RELEASE_URL)" \
  --tag $LATEST_RELEASE_TAG \
  --latest=false \
  --draft=false \
  --prerelease=false

# update latest release assets
ASSETS=$(gh release view $PRE_RELEASE --json assets --jq '.assets[] | .name')
for ASSET in $ASSETS; do
  TARGET_ASSET_FILENAME=$(echo $ASSET | sed -E 's/[0-9]+\.[0-9]+\.[0-9]+/latest/g')
  rm -f $TARGET_ASSET_FILENAME
  echo "upload release '$PRE_RELEASE' asset '$ASSET' as '$TARGET_ASSET_FILENAME' to release '$LATEST_RELEASE_TAG'"
  gh release download $PRE_RELEASE --pattern $ASSET -O $TARGET_ASSET_FILENAME
  if ! gh release upload $LATEST_RELEASE_TAG $TARGET_ASSET_FILENAME --clobber; then
    error_message="Failed to upload asset $TARGET_ASSET_FILENAME"
    [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
    echo "Error: $error_message"
  fi
  # upload latest to s3
  S3_FILENAME=$(echo $TARGET_ASSET_FILENAME | sed -E 's/-latest-.+$/.latest.zip/')
  echo "upload '$ASSET' to s3 as '$S3_FILENAME'"
  # ensure we have a AWS_ACCESS_KEY_ID
  if [[ -z "${AWS_ACCESS_KEY_ID}" ]] || [[ -z "${AWS_SECRET_ACCESS_KEY}" ]]; then
    ionos.wordpress.log_error "aws secrets are not complete. AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY is necessary"
  else
    docker run -i --rm -v "$(pwd)/$TARGET_ASSET_FILENAME":"/tmp/$TARGET_ASSET_FILENAME" --entrypoint bash amazon/aws-cli - <<EOF
      export AWS_REQUEST_CHECKSUM_CALCULATION=when_required
      export AWS_RESPONSE_CHECKSUM_VALIDATION=when_required

      aws configure set aws_access_key_id "$AWS_ACCESS_KEY_ID"
      aws configure set aws_secret_access_key "$AWS_SECRET_ACCESS_KEY"

      aws --endpoint-url https://s3-de-central.profitbricks.com s3 cp /tmp/$TARGET_ASSET_FILENAME s3://web-hosting/ionos-group/$S3_FILENAME
EOF

    if [[ $? -ne 0 ]]; then
      error_message="Failed to upload asset $TARGET_ASSET_FILENAME to S3"
      [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
      echo "Error: $error_message"
    fi
  fi

  rm -f $TARGET_ASSET_FILENAME

  #
  # create/update <plugin>-latest.json file (example : ionos-essentials.info.json)
  #
  {
    # example: 1.2.3
    VERSION=$(echo $ASSET | sed -E 's/.*-([0-9]+\.[0-9]+\.[0-9]+)-.*/\1/')
    # example: ionos-essentials
    PLUGIN=$(echo $ASSET | sed -E 's/^(.*)-[0-9]+\.[0-9]+\.[0-9]+.*/\1/')
    # example : ionos-essentials/ionos-essentials.php
    SLUG="${PLUGIN}/${PLUGIN}.php"
    # example: https://github.com/lgersman/ionos-wordpress/releases/download/%40ionos-wordpress%2Fessentials%400.1.3/ionos-essentials-0.1.3-php7.4.zip
    PACKAGE="https://github.com/$GITHUB_OWNER_REPO/releases/download/$(printf $PRE_RELEASE | jq -Rrs '@uri')/$ASSET"
    # CHANGELOG is the release note of the pre-release (aka the changelog markdown of the release)
    CHANGELOG="$(gh release view $PRE_RELEASE --json body --jq '.body')"

    # Convert markdown in CHANGELOG to HTML using a Node.js package
    CHANGELOG_HTML=$(echo "$CHANGELOG" | npx marked)

    INFO_JSON_FILENAME="${PLUGIN}-info.json"

    jq -n \
      --arg version "$VERSION" \
      --arg slug "$SLUG" \
      --arg package "$PACKAGE" \
      --arg changelog "$CHANGELOG_HTML" \
      '{version: $version, slug: $slug, package: $package, sections : { changelog: $changelog }}' > "$INFO_JSON_FILENAME"

    if ! gh release upload $LATEST_RELEASE_TAG $INFO_JSON_FILENAME --clobber; then
      $error_message="Failed to upload asset $INFO_JSON_FILENAME"
      [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
      echo "Error: $error_message"
    fi
    rm -f $INFO_JSON_FILENAME
  }
done

# Remove the 'pre-release' flag from the PRE_RELEASE
gh release edit "$PRE_RELEASE" --prerelease=false --draft=false --latest=true

ionos.wordpress.log_info "Removed 'pre-release' flag from release '$PRE_RELEASE'"

readonly success_message="Successfully updated release '$LATEST_RELEASE_TAG' (https://github.com/$GITHUB_OWNER_REPO/releases/tag/%40ionos-wordpress%2Flatest) to point to release '${PRE_RELEASE}' ($PRE_RELEASE_URL)"
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
  # changed packages computed by changeset
  CHANGED_PACKAGES=$(echo "$CHANGESET_STATUS_JSON" | jq -r '.releases[] | "* \(.name)(\(.oldVersion)->\(.newVersion))"')
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
