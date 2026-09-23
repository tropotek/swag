#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ -n "$(git status --porcelain)" ]; then
  echo "Uncommitted changes: commit first so the bundle matches HEAD." >&2
  exit 1
fi

rm -rf dist
mkdir -p dist/noteboard-app dist/public_html

git archive HEAD | tar -x -C dist/noteboard-app

docker compose run --rm node sh -c 'npm ci && npm run build'

docker compose run --rm --no-deps app composer install \
  --working-dir=/app/dist/noteboard-app --no-dev --optimize-autoloader --no-interaction

cp -a dist/noteboard-app/public/. dist/public_html/
cp -a public/build dist/public_html/build
cp deploy/cpanel/index.php dist/public_html/index.php
cp deploy/cpanel/.htaccess dist/public_html/.htaccess

rm -rf dist/noteboard-app/public dist/noteboard-app/tests dist/noteboard-app/deploy dist/noteboard-app/skills dist/noteboard-app/docs \
  dist/noteboard-app/Dockerfile dist/noteboard-app/.dockerignore \
  dist/noteboard-app/docker-compose.yml dist/noteboard-app/docker-compose-live.yml

tar -czf dist/noteboard-cpanel.tar.gz -C dist noteboard-app public_html
echo "Built dist/noteboard-cpanel.tar.gz"
