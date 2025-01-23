# syntax=docker/dockerfile:1.4

# FROM mcr.microsoft.com/devcontainers/base:ubuntu
FROM mcr.microsoft.com/devcontainers/php:8.3-bullseye

# always use bash as default shell for the docker file
SHELL ["/bin/bash", "-c"]

RUN export DEBIAN_FRONTEND=noninteractive

# Install the xz-utils package
# RUN apt-get update && apt-get install -y xz-utils

RUN (type -p wget >/dev/null || (sudo apt update && sudo apt-get install wget -y)) \
  && sudo mkdir -p -m 755 /etc/apt/keyrings \
  && wget -qO- https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo tee /etc/apt/keyrings/githubcli-archive-keyring.gpg > /dev/null \
  && sudo chmod go+r /etc/apt/keyrings/githubcli-archive-keyring.gpg \
  && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
  && sudo apt update \
  && sudo apt install gh entr -y

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

# expose wp-env ports
EXPOSE 8888
EXPOSE 8889

# expose storybook port
EXPOSE 6006

# expose wp-env phpmyadmin port
EXPOSE 9000
# expose wp-env phpmyadmin test port
EXPOSE 9001


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

# # when running in a container, the default WP_ENV_HOME should be used
# ENV WP_ENV_HOME /home/$USERNAME/.wp-env

ENV PNPM_VERSION 9.15.3
ENV PNPM_NODEJS_VERSION 22.10.0
ENV XDEBUG_MODE off

# install pnpm
RUN curl https://get.pnpm.io/install.sh | ENV="$HOME/.bashrc" PNPM_VERSION="$PNPM_VERSION" SHELL="$(which bash)" bash -

ENV PNPM_HOME /home/$USERNAME/.local/share/pnpm
ENV PATH $PNPM_HOME:$PATH
RUN pnpm env use --global $PNPM_NODEJS_VERSION
RUN pnpm config set store-dir home/$USERNAME/.pnpm-store
RUN pnpx playwright install --with-deps chromium
