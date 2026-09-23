# Swag 🗞️

[![Docs](https://img.shields.io/badge/docs-tropotek.github.io%2Fswag-2f6feb)](https://tropotek.github.io/swag/)

**[Read the documentation →](https://tropotek.github.io/swag/)**

*A swag is the Aussie bedroll you carry everything in. This one carries your notes.*

A small private website for Markdown pages. You chat with an AI assistant ("add this to
the swag mate"), it posts pages through a token-authenticated REST API, and you read them later from any browser,
including your phone when you're away from home.

For example: you're on your desktop asking your agent to spec out a shopping list, or the
parts list for a project you're planning. Tell it to "chuck that in the swag", and it's
posted as a page you can pull up on your phone at the shop or the hardware store — no
copy-pasting between devices.

- **AI-friendly API.** Per-user Sanctum tokens with full CRUD on `/api/pages`. Errors are
  always JSON.
- **Phone-friendly reading.** A responsive Bootstrap 5 feed. Markdown tables, code and lists
  render safely.
- **No public sign-up.** An admin creates accounts with temporary passwords.
- **Cheap to host.** Docker for development, and plain cPanel shared hosting (fixed
  `public_html`) for production, on SQLite.

## Quick start

```bash
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

Then open <http://localhost:8080>.

Post a page:

```bash
curl -X POST http://localhost:8080/api/pages \
  -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title": "Hello", "body_markdown": "# Hello\n\nFrom the API."}'
```

## Documentation

Full docs: **<https://tropotek.github.io/swag/>**. The same pages are in [`docs/`](docs/).

| | |
|---|---|
| [Getting started](docs/getting-started.md) | Local development, containers, tests |
| [Using Swag](docs/user-guide.md) | Pages, API tokens, accounts, managing users |
| [REST API](docs/api.md) | Endpoint reference |
| [AI assistants](docs/ai-assistants.md) | Connecting any AI agent, with a portable skill |
| [Architecture](docs/architecture.md) | Code map, data model, security model |
| [Deploying to cPanel](docs/deployment-cpanel.md) | Building and publishing the production bundle |

## Tests

```bash
docker compose run --rm app php artisan test
```

## Stack

Laravel 13 · PHP 8.5 · SQLite · Sanctum · Blade + Bootstrap 5 · Vite · Pest · FrankenPHP (dev) ·
Apache/cPanel (production)
