---
title: Getting started
nav_order: 2
---

# Getting started

Development runs entirely in Docker. You don't need PHP, Composer or Node on the host.

## Requirements

- Docker with Compose v2 (`docker compose version`)
- Git

## First run

```bash
git clone <repo-url> Swag && cd Swag
cp .env.example .env

docker compose build app
docker compose run --rm app composer install
docker compose run --rm node sh -c 'npm ci && npm run build'

touch database/database.sqlite
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate

docker compose up -d app
docker compose exec app php artisan swag:create-admin
```

Open <http://localhost:8080> and log in with the admin account you just created.

`swag:create-admin` refuses to run once an admin exists. Pass `--force` to add another.

## The containers

| Service | Image | Purpose |
|---|---|---|
| `app` | `swag-dev` (built from `Dockerfile`, FrankenPHP on PHP 8.5) | Serves the site on port 80 inside the container. Runs `php`, `composer` and `artisan`. |
| `node` | `node:22-alpine` (profile `tools`) | Runs `npm` to build assets. Started on demand with `docker compose run`. |

The project directory is bind-mounted at `/app`, so code changes are live without a rebuild.
The SQLite database lives at `database/database.sqlite` and survives container rebuilds.

### Settings

These are read by Compose from the shell environment or `.env`:

| Variable | Default | Effect |
|---|---|---|
| `APP_PORT` | `8080` | Host port for the site |
| `UID` / `GID` | `1000` | User the containers run as, so files they write stay owned by you |

FrankenPHP is started with `SERVER_NAME=:80`, so it serves plain HTTP and doesn't try to issue
certificates. Put a reverse proxy in front if you want HTTPS locally.

### `docker-compose-live.yml`

This is a byte-identical copy of `docker-compose.yml`. On the maintainer's machine, a
hosting script starts any project that has this file. Keep the two in sync unless the live
config genuinely needs to differ.

## Everyday commands

```bash
docker compose up -d app                               # start
docker compose down                                    # stop
docker compose run --rm app php artisan test           # run the test suite
docker compose run --rm node npm run build             # rebuild CSS/JS after frontend edits
docker compose run --rm app php artisan migrate        # apply new migrations
docker compose exec app php artisan route:list         # list routes
```

## Tests

The suite uses [Pest](https://pestphp.com) with an in-memory SQLite database, so it never
touches your dev data. Tests live in `tests/Feature/`:

| File | Covers |
|---|---|
| `AuthTest.php` | Login, logout, throttling, no registration routes |
| `PasswordChangeTest.php` | Forced password change |
| `AccountTest.php` | Name and email updates |
| `CreateAdminCommandTest.php` | The `swag:create-admin` command |
| `PageModelTest.php` | Markdown rendering and sanitising |
| `PagePolicyTest.php` | Owner-only access |
| `WebPagesTest.php` | Feed, page view, edit, delete |
| `Api/PagesApiTest.php` | The REST API |
| `TokensTest.php` | Creating and revoking API tokens |
| `Admin/UsersTest.php` | The admin Users screen |

## Frontend

Bootstrap 5 is installed from npm and bundled by Vite. The single entry point is
`resources/js/app.js`, which imports Bootstrap's CSS and JS plus `resources/css/app.css`.
Views are Blade templates in `resources/views/` extending `layouts/app.blade.php`.
