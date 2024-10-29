# syntax=docker/dockerfile:1.4

# FROM mcr.microsoft.com/devcontainers/base:ubuntu
FROM mcr.microsoft.com/devcontainers/php:8.3-bullseye

RUN export DEBIAN_FRONTEND=noninteractive

# Install the xz-utils package
# RUN apt-get update && apt-get install -y xz-utils

RUN (type -p wget >/dev/null || (sudo apt update && sudo apt-get install wget -y)) \
  && sudo mkdir -p -m 755 /etc/apt/keyrings \
  && wget -qO- https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo tee /etc/apt/keyrings/githubcli-archive-keyring.gpg > /dev/null \
  && sudo chmod go+r /etc/apt/keyrings/githubcli-archive-keyring.gpg \
  && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
  && sudo apt update \
  && sudo apt install gh -y

ARG USERNAME=vscode

# expose PHP xdebug port 9003
EXPOSE 9003

# expose wp-env ports
EXPOSE 8888
EXPOSE 8889

# expose storybook port
EXPOSE 6006

# doesnt work anymore as of 2024-10-29 because of devcontainer.json spec changes
# 
# # enable persisted bash completion
# # https://code.visualstudio.com/remote/advancedcontainers/persist-bash-history
# RUN SNIPPET="export PROMPT_COMMAND='history -a' && export HISTFILE=/commandhistory/.bash_history" \
#     && mkdir /commandhistory \
#     && touch /commandhistory/.bash_history \
#     && chown -R $USERNAME /commandhistory \
#     && echo "$SNIPPET" >> "/home/$USERNAME/.bashrc"

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

ENV WP_ENV_HOME=./wp-env-home
ENV PNPM_VERSION=9.12.3
ENV PNPM_NODEJS_VERSION=20.17.0
ENV XDEBUG_MODE=off

# install pnpm
RUN curl https://get.pnpm.io/install.sh | ENV="$HOME/.bashrc" PNPM_VERSION="$PNPM_VERSION" SHELL="$(which bash)" bash -
