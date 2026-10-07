---
name: swag
description: Use when the user says "add this to the swag", "chuck it in the swag", "put that in my swag" or similar, asks what's in the swag, or otherwise wants something saved as a page to read later (e.g. on their phone), or wants Swag pages listed, found, updated or deleted.
---

# Swag

Swag is the user's private site of Markdown pages. "Add this to the swag" means post it as a page via the REST API; the user reads it later in a browser.

## Configuration

Needs env vars `SWAG_URL` (no trailing slash) and `SWAG_TOKEN` (from the site's **API tokens** page). Check with `[ -n "$SWAG_URL" ] && [ -n "$SWAG_TOKEN" ] && echo set`; if either is missing, stop and ask the user. Never print the token, write it to files, or type it literally: always use `$SWAG_TOKEN`. Use `SWAG_URL` exactly as configured.

## API

The API describes itself, including content rules, limits, rate limit and errors. Read the OpenAPI document (no token needed) before calling it, not from memory:

```bash
curl -sS "$SWAG_URL/api/openapi.json" | jq '.paths | map_values(map_values(.summary?))'
curl -sS "$SWAG_URL/api/openapi.json" | jq '.paths["/pages"].post'   # one operation in full
```

Send `Authorization: Bearer $SWAG_TOKEN` and `Accept: application/json`. Search with `q` rather than paging through the list:

```bash
curl -sS -G "$SWAG_URL/api/pages" --data-urlencode "q=tap repair" \
  -H "Authorization: Bearer $SWAG_TOKEN" -H "Accept: application/json" | jq '.data[] | {id, title, url}'
```

After creating a page, tell the user its `url`. Make each page stand alone: the reader won't have this chat.

To put an image or file in a page, upload it first, then paste the returned `markdown` into the page body (there are no token-less URLs; files are private to the user):

```bash
curl -sS -X POST "$SWAG_URL/api/media" -H "Authorization: Bearer $SWAG_TOKEN" -H "Accept: application/json" \
  -F "file=@photo.png" | jq -r '.data.markdown'
```

Max 25 MB. Executables and scripts are rejected: zip them first.

To read a file a page refers to — an image you need to look at, for instance — take the `/media/{uuid}/{name}` path out of the page's Markdown and prefix it with `/api`:

```bash
curl -sS "$SWAG_URL/api/media/6f1c…/photo.png" -H "Authorization: Bearer $SWAG_TOKEN" -o photo.png
```

The name segment is cosmetic, so `/api/media/{uuid}` works too.

## Cautions

- Confirm before any `DELETE`, or a `PATCH` that replaces the body.
- To update "the page about X", search with `q` and `PATCH` the existing page rather than posting a duplicate.
- On `401`, ask the user for a new token.
