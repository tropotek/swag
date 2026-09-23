---
title: Architecture
nav_order: 7
---

# Architecture

## Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 |
| PHP | 8.5 (dev image and production) |
| Database | SQLite |
| API auth | Laravel Sanctum personal access tokens |
| Web auth | Session login written in-app (no starter kit) |
| UI | Blade templates, Bootstrap 5, a few lines of vanilla JS |
| Assets | Vite |
| Tests | Pest |
| Dev server | FrankenPHP in Docker |
| Production | Apache on cPanel shared hosting |

## Code map

```
app/
  Console/Commands/CreateAdmin.php      swag:create-admin
  Http/Controllers/
    Auth/LoginController.php            login, logout
    AccountController.php               name and email
    PasswordController.php              change password (and forced change)
    PageController.php                  web feed, view, edit, delete
    TokenController.php                 API token screen
    Api/PageController.php              REST API
    Admin/UserController.php            admin: list, create, delete users
    Admin/UserPasswordController.php    admin: set temporary password
  Http/Middleware/
    EnsurePasswordChanged.php           alias password.changed
    EnsureAdmin.php                     alias admin
  Http/Requests/PageRequest.php         shared page validation (web + API)
  Http/Resources/PageResource.php       API JSON shape
  Models/User.php, Models/Page.php
  Policies/PagePolicy.php               owner-only, 404 for everyone else
routes/web.php, routes/api.php
resources/views/                        Blade templates
deploy/                                 cPanel build script, index.php, .htaccess
skills/swag/                       portable Agent Skill (any agent)
```

## Data model

- **users:** Laravel's standard table plus `is_admin` and `must_change_password` (booleans).
- **pages:** `id`, `user_id` (cascades on user delete), `title`, `body_markdown`, timestamps.
  Indexed on `(user_id, created_at)` for the feed.
- **personal_access_tokens:** Sanctum's table. It's polymorphic with no foreign key, so user
  deletion removes tokens explicitly.

Markdown is stored as posted and rendered on every view, so rendering rules can change
without a migration.

## Security model

| Concern | Measure |
|---|---|
| Who can sign up | Nobody. Admins create accounts. There are no registration, reset or verification routes. |
| First admin | Created only from the command line. |
| Privilege escalation | `is_admin` and `must_change_password` are never mass-assignable. They're set only via `forceFill`/`forceCreate`. |
| Temporary passwords | `must_change_password` locks the user to the change-password form, and the new password must differ from the temporary one. |
| Brute force | Login is throttled to 5 attempts per minute. The API allows 60 requests per minute per user. |
| Session fixation / CSRF | The session is regenerated on login and invalidated on logout. Every form has a CSRF token. |
| Data isolation | Every page lookup goes through `PagePolicy`, which denies non-owners **as not found (404)**. Admins get no exemption. |
| Stored XSS from AI content | CommonMark runs with `html_input: escape`, `allow_unsafe_links: false` and `max_nesting_level: 50`. Titles are always escaped. |
| Hostile or runaway input | Title ≤ 255 characters, body ≤ 1,000,000. Deep nesting is capped so rendering can't exhaust memory. |
| Transport | Production `.htaccess` forces HTTPS with a 308 redirect, and the session cookie is `Secure`. |
| API clients without `Accept` | `/api/*` always renders errors as JSON, never an HTML redirect. |

## Why things are the way they are

- **No Laravel Breeze.** Breeze hasn't been updated since Laravel 12. Once registration,
  password reset and email verification are removed, only login, logout and the account
  screens are left, and hand-written versions are less code than re-skinning Breeze.
- **SQLite.** One file, nothing to provision, and it works the same in Docker and on
  shared hosting.
- **`usePublicPath()` in the deploy `index.php`.** The cPanel web root is fixed to
  `public_html`, so the app lives next to it rather than inside it. The public path has to be
  set in the front controller, because `.env` isn't loaded yet when `bootstrap/app.php` runs.
- **404 instead of 403.** A 403 confirms that a page ID exists.
