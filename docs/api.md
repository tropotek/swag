---
title: REST API
nav_order: 4
---

# REST API

The API lets AI assistants and scripts manage the pages of the user who owns the token.

- **Base URL:** `https://your-site.example/api`
- **Format:** JSON in and out. Errors are always JSON, even without an `Accept` header.
- **Rate limit:** 60 requests per minute per user.

## Authentication

Create a token on the site's **API tokens** page and send it as a Bearer token:

```
Authorization: Bearer 1|abc123...
Accept: application/json
```

Tokens don't expire. Revoke them on the **API tokens** page.

Always call the `https://` URL. Plain `http://` requests get a `308` redirect to HTTPS,
which well-behaved clients follow with the same method and body.

## The page object

```json
{
  "id": 12,
  "title": "Tap repair — quick guide",
  "body_markdown": "# Tools\n\n| Tool | ...",
  "url": "https://your-site.example/pages/12",
  "created_at": "2026-09-23T08:43:36+00:00",
  "updated_at": "2026-09-23T08:51:02+00:00"
}
```

| Field | Type | Rules |
|---|---|---|
| `title` | string | Required on create. At most 255 characters. |
| `body_markdown` | string | Required on create. At most 1,000,000 characters. GitHub-flavoured Markdown. |
| `url` | string | Read-only. Link to the page in the web UI. |

**How Markdown renders:** raw HTML in the body is displayed as literal text. Links using
`javascript:`, `data:`, `vbscript:` or `file:` are removed. Nesting deeper than 50 levels is
flattened. There's no image upload, so images need a public URL.

## Endpoints

### List pages

`GET /api/pages?page=1`

Returns your pages, newest first, 20 per page.

```json
{
  "data": [ { "id": 12, "title": "...", "...": "..." } ],
  "links": { "first": "...?page=1", "last": "...?page=3", "prev": null, "next": "...?page=2" },
  "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 47 }
}
```

### Create a page

`POST /api/pages` returns **201** with `{ "data": <page> }`.

```bash
jq -n --arg title "Hello" --arg body "# Hello\n\nFrom a script." \
  '{title: $title, body_markdown: $body}' |
curl -sS -X POST "$SWAG_URL/api/pages" \
  -H "Authorization: Bearer $SWAG_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  --data-binary @-
```

Build the JSON with a real encoder (`jq`, `json.dumps`, `JSON.stringify`). Markdown is full
of quotes and newlines that break hand-written JSON.

### Read a page

`GET /api/pages/{id}` returns **200** with `{ "data": <page> }`.

### Update a page

`PATCH /api/pages/{id}` (or `PUT`) returns **200** with `{ "data": <page> }`.

Send only the fields you're changing. A field you send can't be empty.

```bash
curl -sS -X PATCH "$SWAG_URL/api/pages/12" \
  -H "Authorization: Bearer $SWAG_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"title": "A better title"}'
```

### Delete a page

`DELETE /api/pages/{id}` returns **204** with no body.

## Errors

| Status | When | Body |
|---|---|---|
| `401` | Token missing, wrong or revoked | `{"message": "Unauthenticated."}` |
| `404` | Page doesn't exist **or belongs to someone else** | `{"message": "..."}` |
| `422` | Validation failed | `{"message": "...", "errors": {"title": ["..."]}}` |
| `429` | Rate limit exceeded | `{"message": "Too Many Attempts."}` with a `Retry-After` header |

Other users' pages return `404`, not `403`, so page IDs can't be probed.
