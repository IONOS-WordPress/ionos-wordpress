# syntax=docker/dockerfile:1.4

ARG PYTHON_VERSION

FROM python:${PYTHON_VERSION}-alpine

ARG DENNIS_VERSION

RUN <<EOF
  apk add --update \
    pipx

  rm -rf /var/cache/apk/*

  pipx install "dennis>=${DENNIS_VERSION}"
EOF

ENTRYPOINT ["/root/.local/bin/dennis-cmd"]

VOLUME ["/project"]
WORKDIR /project
