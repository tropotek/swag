# REST API

The API lets AI assistants and scripts manage the pages of the user who owns the token.

- **Base URL:** `https://your-site.example/api`
- **Format:** JSON in and out. Errors are always JSON, even without an `Accept` header.
- **Rate limit:** 60 requests per minute per user, or 120 for `POST /media`.

The same API is described machine-readably at `GET /api/openapi.json` (OpenAPI 3.1, no token needed), so
an AI assistant can discover the operations and parameters itself.

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
flattened. Images and files come from [Upload a file](#upload-a-file): paste the returned `markdown`
into the body. Images show in the page; other files are links that open in a new tab, where audio and
video play and everything else downloads.

## Endpoints

### List pages

`GET /api/pages?q=tap&sort=relevance&per_page=50&page=1`

Returns your pages, 50 per page by default.

| Param | Meaning |
|---|---|
| `q` | Search title and body. Every word must match (prefix match, so `wash` finds `washer`). Optional. |
| `per_page` | `20`, `50` (default), `100` or `200`. Anything else falls back to `50`. |
| `sort` | See below. |

| `sort` | Order |
|---|---|
| `updated` (default) | Most recently updated first |
| `created` | Newest created first |
| `title` | Title A–Z, ignoring case |
| `relevance` | Best match first, title matches weighted above body matches. Default when `q` is set. |

An unknown `sort` falls back to `updated` (or `relevance` when `q` is set). The `links` URLs keep your `q`, `sort` and `per_page` values.

```json
{
  "data": [ { "id": 12, "title": "...", "...": "..." } ],
  "links": { "first": "...?page=1", "last": "...?page=3", "prev": null, "next": "...?page=2" },
  "meta": { "current_page": 1, "last_page": 3, "per_page": 50, "total": 147 }
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

### Upload a file

`POST /api/media` returns **201** with `{ "data": <media> }`. Send the file as `multipart/form-data` in the
`file` field. The maximum size is 25 MB.

```bash
curl -sS -X POST "$SWAG_URL/api/media" \
  -H "Authorization: Bearer $SWAG_TOKEN" -H "Accept: application/json" \
  -F "file=@photo.png"
```

```json
{
  "data": {
    "id": 7,
    "url": "https://your-site.example/media/6f1c…/photo.png",
    "name": "photo.png",
    "mime_type": "image/png",
    "size": 48213,
    "markdown": "![photo.png](https://your-site.example/media/6f1c…/photo.png)"
  }
}
```

Paste `data.markdown` into a page body. Images display in the page. Every other file is a link that opens
in a new tab: audio and video play there in the browser's own player, and everything else, PDFs included,
downloads.

Uploaded files are private: only you can open them, and only while logged in to the site. Executable and
script types (`exe`, `bat`, `cmd`, `sh`, `js`, `php`, `py` and similar) are rejected with a `422`; put
them in a zip file and upload that.

The type is detected from the file's content, not its name, so renaming a file doesn't change how it's
served. Uploads have their own rate limit of **120 requests per minute**, separate from the 60 per minute
that the rest of the API shares.

## Errors

| Status | When | Body |
|---|---|---|
| `401` | Token missing, wrong or revoked | `{"message": "Unauthenticated."}` |
| `404` | Page doesn't exist **or belongs to someone else** | `{"message": "..."}` |
| `422` | Validation failed, or an upload was too large or a blocked type | `{"message": "...", "errors": {"title": ["..."]}}` |
| `429` | Rate limit exceeded | `{"message": "Too Many Attempts."}` with a `Retry-After` header |

Other users' pages return `404`, not `403`, so page IDs can't be probed.
