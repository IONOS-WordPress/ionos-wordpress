# syntax=docker/dockerfile:1.4

FROM debian:12-slim

# dennis version from .env
ARG DENNIS_VERSION

ENV foo=bar

RUN apt-get update && \
  apt-get install  --no-install-suggests --no-install-recommends --yes pipx && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/* && \
  pipx install "dennis>=${DENNIS_VERSION}"

ENTRYPOINT ["/root/.local/bin/dennis-cmd"]

VOLUME ["/project"]
WORKDIR /project
