# syntax=docker/dockerfile:1.4

# php version of the docker image
ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli as base

# rector version to embed into docker image
ARG RECTOR_VERSION

# rector version to use in docker image
ENV RECTOR_VERSION=${RECTOR_VERSION}

# Fix add-apt-repository is broken with non-UTF-8 locales, see https://github.com/oerdnj/deb.sury.org/issues/56
ENV LC_ALL C.UTF-8
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && \
  apt-get install -y --no-install-recommends \
  git unzip && \
  # Cleanup
  apt-get autoremove -y && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/* && \
  rm -rf /var/cache/apt/* && \
  groupadd --gid 1000 php \
  && useradd --uid 1000 --gid php --shell /bin/bash --create-home php

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY --chmod=755 ./packages/docker/rector-php/entrypoint.sh /usr/bin

USER php
RUN echo "RECTOR_VERSION=${RECTOR_VERSION}"
RUN cd ~ && pwd && composer require --dev "rector/rector:${RECTOR_VERSION}"

VOLUME ["/project"]
WORKDIR /project

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
