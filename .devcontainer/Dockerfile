# syntax=docker/dockerfile:1@sha256:4edf897a3ffa55b89f906fc8cc78afdb3f1834cc9c7083565e611a8a7d5fe99e

ARG TARGETARCH

FROM scratch AS composer

ADD --chmod=0755 --checksum=sha256:7a2d379d5b8ffdaa028580ef26494c36d2feef4b178d3dd1473a4dbc5e17c8d6 \
    https://getcomposer.org/download/2.10.3/composer.phar \
    /tmp/composer.phar
ADD --checksum=sha256:2b2df89fb2581e669b4668b451097d4a03f4dc2d7a06df197554064494a90a31 \
    https://raw.githubusercontent.com/composer/composer.github.io/e7f28b7200249f8e5bc912b42837d4598c74153a/snapshots.pub \
    /tmp/keys.dev.pub
ADD --checksum=sha256:d8f3e350437bf3b5342dbf046064ebd1f017ebc720955433194360a01cfd074f \
    https://raw.githubusercontent.com/composer/composer.github.io/e7f28b7200249f8e5bc912b42837d4598c74153a/releases.pub \
    /tmp/keys.tags.pub

FROM scratch AS node-amd64

ADD --checksum=sha256:28ea0a438de9a13bd7891fd758eee6c7819ee8af5442dfd8d45bd65e56d4228c \
    https://nodejs.org/dist/v24.22.0/node-v24.22.0-linux-x64.tar.gz \
    /tmp/node.tgz

FROM scratch AS node-arm64

ADD --checksum=sha256:b7e7991d5407815adfa74a2a9cd4db683552c8ce32ff4a295c22e972849c3f5d \
    https://nodejs.org/dist/v24.22.0/node-v24.22.0-linux-arm64.tar.gz \
    /tmp/node.tgz

FROM node-${TARGETARCH} AS node

FROM ghcr.io/php/pie:1.5.2-bin@sha256:a254c78b51ca34daef0541fa1cf0147385c93ba5ca799868392275d947f00fe7 AS pie
FROM docker.io/library/php:8.5-cli-trixie@sha256:01a109229f4465bc9ef042d9198f09a4d9da7775a825dbd8572dcdbd8756c4d3

COPY --from=composer /tmp/composer.phar /usr/local/bin/composer
COPY --from=node /tmp/node.tgz /tmp/node.tgz

RUN --mount=type=bind,from=pie,source=/pie,target=/usr/local/bin/pie \
    apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get upgrade --yes --no-install-recommends \
    && DEBIAN_FRONTEND=noninteractive apt-get install --yes --no-install-recommends ca-certificates git libicu-dev libtool libzip-dev make openssh-client python3 sudo unzip zip \
    && docker-php-ext-install -j"$(nproc)" intl pcntl zip \
    && pie install --no-cache 'apcu/apcu:5.1.28' \
    && pie install --no-cache 'phpredis/phpredis:6.3.0' \
    && pie install --no-cache 'xdebug/xdebug:3.5.3' \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && groupadd --gid 1000 devcontainer \
    && useradd --uid 1000 --gid devcontainer --shell /bin/bash --create-home devcontainer \
    && install --directory --owner devcontainer --group devcontainer \
        /home/devcontainer/.composer \
        /home/devcontainer/.local \
        /home/devcontainer/.local/bin \
        /home/devcontainer/.local/lib \
        /home/devcontainer/.local/lib/node_modules \
    && printf '%s\n' 'devcontainer ALL=(root) NOPASSWD:ALL' > /etc/sudoers.d/devcontainer \
    && chmod 0440 /etc/sudoers.d/devcontainer \
    && tar --extract --gzip --file /tmp/node.tgz --directory /usr/local --strip-components=1 --no-same-owner --exclude='*/README.md' --exclude='*/CHANGELOG.md' --exclude='*/LICENSE' \
    && ln --symbolic /usr/local/bin/node /usr/local/bin/nodejs \
    && rm --force -- /tmp/node.tgz \
    && rm -rf /root/.pie /var/lib/apt/lists/*

COPY php/ "$PHP_INI_DIR/conf.d/"
COPY --chown=devcontainer:devcontainer --from=composer /tmp/keys.dev.pub /tmp/keys.tags.pub /home/devcontainer/.composer/

ENV NPM_CONFIG_PREFIX=/home/devcontainer/.local
ENV PATH=/home/devcontainer/.local/bin:${PATH}

USER devcontainer
