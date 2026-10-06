FROM dunglas/frankenphp:1-php8.5

RUN install-php-extensions pdo_sqlite intl zip pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} app \
 && useradd -u ${UID} -g app -m app \
 && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
 && chown -R app:app /config/caddy /data/caddy

# Allow 25 MB uploads (swag.media.max_kb) with headroom for the multipart body.
RUN printf 'upload_max_filesize=26M\npost_max_size=28M\n' > "$PHP_INI_DIR/conf.d/zz-swag-uploads.ini"

USER app
ENV SERVER_NAME=:80
WORKDIR /app
