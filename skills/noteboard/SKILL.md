---
name: noteboard
description: Use when the user asks to save, post, publish, send or "put on my noteBoard" something to read later (e.g. on their phone), or to list, find, update or delete pages on their noteBoard site.
---

# noteBoard

noteBoard is the user's private site of Markdown pages. You post pages through its REST API, and the user reads them later in a browser.

## Configuration

Two environment variables:

| Variable | Example |
|---|---|
| `NOTEBOARD_URL` | `https://notes.example.com` (no trailing slash) |
| `NOTEBOARD_TOKEN` | the user's API token, from the site's **API tokens** page |

Check both with `[ -n "$NOTEBOARD_URL" ] && [ -n "$NOTEBOARD_TOKEN" ] && echo set`. If either is missing, stop and ask the user to set it. If the token is set in a shell profile, it must be single-quoted, because tokens contain `|`. Never print the token, write it into files, or put it literally in a command. Always reference `$NOTEBOARD_TOKEN`.

## API

All requests send `Authorization: Bearer $NOTEBOARD_TOKEN` and `Accept: application/json`.

| Action | Request | Success |
|---|---|---|
| Create | `POST /api/pages` with `{"title", "body_markdown"}` | 201, `data.url` |
| List (newest first, 20/page) | `GET /api/pages?page=N` | 200, `data[]`, `meta.total` |
| Read | `GET /api/pages/{id}` | 200 |
| Update (send only changed fields) | `PATCH /api/pages/{id}` | 200 |
| Delete | `DELETE /api/pages/{id}` | 204 |

## Posting a page

Markdown contains quotes and newlines that break hand-written JSON. Write the body to a temp file and let `jq` encode it:

```bash
body=$(mktemp)
cat > "$body" <<'MD'
# Heading

Markdown content here. Tables, lists and fenced code all render.
MD
jq -n --arg title "Short descriptive title" --rawfile body "$body" \
  '{title: $title, body_markdown: $body}' |
curl -sS -X POST "$NOTEBOARD_URL/api/pages" \
  -H "Authorization: Bearer $NOTEBOARD_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  --data-binary @- | jq '{id: .data.id, url: .data.url, errors: .errors}'
rm -f "$body"
```

Tell the user the returned `url`.

## Content rules

- Title: at most 255 characters. Body: at most 1,000,000 characters.
- Write Markdown (GitHub-flavoured). Raw HTML is shown as literal text, and `javascript:` links are removed.
- There's no image upload. Images only appear if they're referenced by a public URL.
- Write the page so it stands alone: someone reading it later on a phone won't have this chat.

## Errors

| Status | Meaning |
|---|---|
| 401 | The token is missing, wrong or revoked. Ask the user for a new one. |
| 404 | No such page, or it belongs to another user. |
| 422 | Validation failed. The `errors` object names the field. |
| 429 | Rate limited at 60 requests a minute. Wait, then retry. |

## Common mistakes

- Deleting or overwriting a page without the user explicitly asking. Always confirm before `DELETE` or a `PATCH` that replaces the body.
- Posting a duplicate. When updating "the page about X", list the pages first and `PATCH` the existing one.
- Using `http://` for a site that forces HTTPS. Use the URL exactly as the user configured it.
