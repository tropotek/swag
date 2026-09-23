FROM dunglas/frankenphp:1-php8.5

RUN install-php-extensions pdo_sqlite intl zip pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} app \
 && useradd -u ${UID} -g app -m app \
 && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
 && chown -R app:app /config/caddy /data/caddy

USER app
ENV SERVER_NAME=:80
WORKDIR /app
