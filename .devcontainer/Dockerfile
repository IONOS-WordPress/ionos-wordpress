# syntax=docker/dockerfile:1.4

# FROM mcr.microsoft.com/devcontainers/base:ubuntu
#
# PHP 8.4 rather than the project's stated 8.3 minimum (see AGENTS.md): this interpreter is
# tooling-only - PHPUnit runs inside the wordpress-alpine test container and the
# transpilation-target syntax checks shell out to php:<target>-cli images (see
# scripts/test.sh), so it does not define the supported-PHP floor. 8.4 is required because
# potrans' composer.lock needs >= 8.4.1, and it happens to match
# packages/docker/wordpress-alpine/.env's default ARG_PHP_VERSION, so the tooling PHP and
# the WordPress a developer runs against finally agree.
FROM mcr.microsoft.com/devcontainers/php:8.4-bookworm

# always use bash as default shell for the docker file
SHELL ["/bin/bash", "-c"]

RUN export DEBIAN_FRONTEND=noninteractive

# Install the xz-utils package
# RUN apt-get update && apt-get install -y xz-utils

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | tee /usr/share/keyrings/yarn-archive-keyring.gpg \
  && (type -p wget >/dev/null || (apt update && apt-get install wget -y)) \
  && mkdir -p -m 755 /etc/apt/keyrings \
  && wget -qO- https://cli.github.com/packages/githubcli-archive-keyring.gpg | tee /etc/apt/keyrings/githubcli-archive-keyring.gpg > /dev/null \
  && chmod go+r /etc/apt/keyrings/githubcli-archive-keyring.gpg \
  && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
  && apt update \
  && apt install gh entr -y

ARG USERNAME=vscode

# enable bash completion persistency
# https://code.visualstudio.com/remote/advancedcontainers/persist-bash-history
RUN SNIPPET="export PROMPT_COMMAND='history -a' && export HISTFILE=/commandhistory/.bash_history" \
  && mkdir /commandhistory \
  && touch /commandhistory/.bash_history \
  && chown -R $USERNAME /commandhistory \
  && echo "$SNIPPET" >> "/home/$USERNAME/.bashrc"

# expose PHP xdebug port 9003
EXPOSE 9003

# expose wordpress-alpine dev/test container ports
EXPOSE 8888
EXPOSE 8889

# expose storybook port
EXPOSE 6006


# create message to show at first vscode run
# COPY .devcontainer/welcome.txt /usr/local/etc/vscode-dev-containers/first-run-notice.txt
COPY <<EOF /usr/local/etc/vscode-dev-containers/first-run-notice.txt
# Setup

At first time you need to install the dependencies using the "pnpm install --frozen-lockfile" command.

# Development

Run "pnpm start" to start the WordPress development environment.

Execute "jq '.scripts' package.json" to see available scripts.

Have fun !

EOF

USER $USERNAME

ENV PNPM_VERSION 11.22.0
ENV PNPM_NODEJS_VERSION 24.18.0
ENV XDEBUG_MODE off

# install pnpm
RUN curl https://get.pnpm.io/install.sh | ENV="$HOME/.bashrc" PNPM_VERSION="$PNPM_VERSION" SHELL="$(which bash)" bash -

ENV PNPM_HOME /home/$USERNAME/.local/share/pnpm
ENV PATH $PNPM_HOME:$PNPM_HOME/bin:$PATH
# pnpm-managed node only symlinks node/npm/pnpm themselves into $PNPM_HOME, not
# npm's own bin dir, so global npm installs (e.g. the claude-code devcontainer
# feature) land outside PATH unless npm's global prefix is pointed at $PNPM_HOME
#
# since pnpm 11, `pnpm env use` only installs the `node` package (just the binary, no
# bundled npm - unlike the official Node.js distribution pnpm 9 used to fetch), so npm
# is installed explicitly as its own global package
RUN pnpm env use --global $PNPM_NODEJS_VERSION \
  && pnpm add --global npm \
  && npm config set prefix "$PNPM_HOME" --location=global \
  && mkdir -p "$HOME/.claude"
RUN pnpm config set store-dir home/$USERNAME/.pnpm-store

# 'chromium' is not a decoration: without it playwright installs the system dependencies for
# firefox and webkit too (libavcodec, libavif, libgav1, libyuv and friends), and nothing in
# this repository uses those browsers - playwright.config.js and playwright-ct.config.js
# both declare a single 'chromium' project, and scripts/test.sh installs only chromium.
# this image is pulled once per CI job, twice per run, so its size is the largest fixed cost
# in the pipeline (see .beans uizd).
# note this installs the *system dependencies* only, not the browser. baking the browser in
# too (see .beans xla3) is deliberately NOT done here: it would add ~300MB to an image that
# is pulled twice per run, and CI would not even use it, because scripts/test.sh points
# PLAYWRIGHT_BROWSERS_PATH at a workspace-relative directory when CI=true so that the host
# side actions/cache can carry it between runs - a restore that already costs only ~7s.
RUN bash -c "pnpx playwright install-deps chromium"

# ---------------------------------------------------------------------------------------
# native CLI tools (ecs-php, rector-php, potrans, dennis-i18n)
#
# these four also exist as docker images under packages/docker/*, which is what developers
# working outside this dev container use. inside it - and in CI, which runs everything
# through it - the same tools run natively instead: that removes a docker-in-docker round
# trip per invocation, three registry pulls and two pushes from the CI workflow, and the
# "first pnpm lint builds five docker images" wait from a fresh checkout.
# scripts/includes/_native-tools.sh holds the dispatch and the full rationale.
#
# the layout mirrors the docker images' deliberately: each tool gets its own COMPOSER_HOME,
# so its binary sits at "$COMPOSER_HOME/vendor/bin/<tool>" exactly as it sits at
# /composer/vendor/bin/<tool> inside the image. that is what lets the shared config files
# resolve their vendor paths through $COMPOSER_HOME in both modes. COMPOSER_HOME is set
# per-invocation rather than globally on purpose - a global value would point every
# unrelated composer call at one tool's private home.
#
# versions come from each tool's committed composer.lock via 'composer install' (never
# 'update'), so both modes run identical versions. that also means this image has to be
# rebuilt when those lockfiles change - see
# .github/shared/actions/devcontainer-image-name/action.yaml, which feeds the tool
# directories into the image tag for exactly that reason.
# ---------------------------------------------------------------------------------------
USER root

ENV IONOS_NATIVE_TOOLS_PREFIX=/opt/ionos-wordpress/tools

# intl and pcntl are the only extensions the three PHP tools need that this base image does
# not already ship (it has ctype/fileinfo/sodium/tokenizer/xmlreader/xmlwriter/posix/
# simplexml). intl needs libicu-dev to compile.
RUN <<EOF
  set -eu
  export DEBIAN_FRONTEND=noninteractive
  apt-get update
  apt-get install -y --no-install-recommends libicu-dev pipx
  docker-php-ext-install -j"$(nproc)" intl pcntl
  rm -rf /var/lib/apt/lists/*
EOF

# copied before the installs so a lockfile change invalidates only from here on
COPY packages/docker/ecs-php/composer.json packages/docker/ecs-php/composer.lock $IONOS_NATIVE_TOOLS_PREFIX/ecs-php/
COPY packages/docker/rector-php/composer.json packages/docker/rector-php/composer.lock $IONOS_NATIVE_TOOLS_PREFIX/rector-php/
COPY packages/docker/potrans/composer.json packages/docker/potrans/composer.lock $IONOS_NATIVE_TOOLS_PREFIX/potrans/

RUN <<EOF
  set -eu
  export COMPOSER_ALLOW_SUPERUSER=1
  for TOOL in ecs-php rector-php potrans; do
    COMPOSER_HOME="$IONOS_NATIVE_TOOLS_PREFIX/$TOOL" composer global install --no-interaction --no-progress
  done

  # tell the phpcs dependency of easy-coding-standard where to find the used standards.
  # mirrors the same step in packages/docker/ecs-php/Dockerfile, against this prefix -
  # phpcs persists it into its own config file, so it has to happen at build time.
  # note this targets easy-coding-standard's *nested* phpcs, which is the one ecs actually
  # loads; the top level vendor/squizlabs copy is configured by the dealerdirect composer
  # plugin during install above, which already writes the correct absolute paths
  ECS_HOME="$IONOS_NATIVE_TOOLS_PREFIX/ecs-php"
  "$ECS_HOME/vendor/symplify/easy-coding-standard/vendor/squizlabs/php_codesniffer/bin/phpcs" \
    --config-set installed_paths \
    "$ECS_HOME/vendor/phpcsstandards/phpcsextra,$ECS_HOME/vendor/phpcsstandards/phpcsutils,$ECS_HOME/vendor/wp-coding-standards/wpcs"

  # each COMPOSER_HOME keeps a download cache that has no value at runtime (~27MB across the
  # three). small next to the image as a whole, but this image is pulled twice per CI run
  rm -rf "$IONOS_NATIVE_TOOLS_PREFIX"/*/cache
EOF

# dennis is a python package. pinned exactly, matching
# packages/docker/dennis-i18n/{.env,Dockerfile} - an open upper bound would let the native
# and dockerized versions drift apart depending on when each was built.
ARG DENNIS_VERSION=1.3.0
RUN PIPX_HOME="$IONOS_NATIVE_TOOLS_PREFIX/dennis-i18n/pipx" \
    PIPX_BIN_DIR="$IONOS_NATIVE_TOOLS_PREFIX/dennis-i18n/bin" \
    pipx install "dennis==$DENNIS_VERSION"

USER $USERNAME

# fail the image build - not somebody's first 'pnpm lint' - if a tool cannot start, which
# is what a missing php extension looks like. the paths must stay in sync with
# IONOS_NATIVE_TOOL_PATHS in scripts/includes/_native-tools.sh
#
# deliberately AFTER the USER switch: rector and ecs create cache directories under /tmp on
# first run, and running them as root here left those root-owned, so the very first
# invocation as $USERNAME then died with "Failed to create /tmp/rector_cached_files/..:
# permission denied". running the probes as the same user that will use the tools both
# avoids that and makes the probe representative.
RUN <<EOF
  set -eu
  "$IONOS_NATIVE_TOOLS_PREFIX/ecs-php/vendor/bin/ecs" --version
  COMPOSER_HOME="$IONOS_NATIVE_TOOLS_PREFIX/rector-php" "$IONOS_NATIVE_TOOLS_PREFIX/rector-php/vendor/bin/rector" --version
  "$IONOS_NATIVE_TOOLS_PREFIX/potrans/vendor/bin/potrans" --version
  # dennis-cmd has no --version flag, so --help is the cheapest "does it start" probe
  "$IONOS_NATIVE_TOOLS_PREFIX/dennis-i18n/bin/dennis-cmd" --help
EOF
