#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ -n "$(git status --porcelain)" ]; then
  echo "Uncommitted changes: commit first so the bundle matches HEAD." >&2
  exit 1
fi

rm -rf dist
mkdir -p dist/swag-app dist/public_html

git archive HEAD | tar -x -C dist/swag-app

docker compose run --rm node sh -c 'npm ci && npm run build'

docker compose run --rm --no-deps app composer install \
  --working-dir=/app/dist/swag-app --no-dev --optimize-autoloader --no-interaction

cp -a dist/swag-app/public/. dist/public_html/
cp -a public/build dist/public_html/build
cp deploy/cpanel/index.php dist/public_html/index.php
cp deploy/cpanel/.htaccess dist/public_html/.htaccess

rm -rf dist/swag-app/public dist/swag-app/tests dist/swag-app/deploy dist/swag-app/skills dist/swag-app/docs \
  dist/swag-app/Dockerfile dist/swag-app/.dockerignore \
  dist/swag-app/docker-compose.yml dist/swag-app/docker-compose-live.yml

tar -czf dist/swag-cpanel.tar.gz -C dist swag-app public_html
echo "Built dist/swag-cpanel.tar.gz"
