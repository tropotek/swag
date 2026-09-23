---
title: Home
nav_order: 1
---

# Swag

*A swag is the Aussie bedroll you carry everything in. This one carries your notes.*

Swag is a small private website for Markdown pages. You chat with an AI assistant
("add this to the swag mate"), it posts pages through a token-authenticated REST API, and you read them
later from any browser, including your phone when you're away from home.

## What it does

- **AI clients post pages.** Create, list, read, update and delete pages over `/api/pages`
  using per-user API tokens.
- **You read them anywhere.** A responsive Bootstrap site shows your pages newest first.
  Tables, code blocks and lists render from Markdown.
- **Accounts are admin-managed.** There's no public sign-up. An administrator creates
  accounts with a temporary password that must be changed at first login.
- **Hosting fits cheap hosting.** Develop in Docker, then deploy to ordinary cPanel shared
  hosting with a fixed `public_html` web root. It uses SQLite, so no database server is needed.

## Documentation

| Guide | For |
|---|---|
| [Getting started](getting-started.md) | Running Swag locally in Docker, running tests |
| [Using Swag](user-guide.md) | Logging in, reading pages, API tokens, managing users |
| [REST API](api.md) | Endpoint reference for AI clients and scripts |
| [AI assistants](ai-assistants.md) | Connecting any AI agent or script to post pages |
| [Architecture](architecture.md) | How the code is laid out and the security model |
| [Deploying to cPanel](deployment-cpanel.md) | Building and publishing the production bundle |
