---
title: Using Swag
nav_order: 3
---

# Using Swag

## Logging in

Log in with the email and password your administrator gave you. Email addresses aren't
case-sensitive, so a phone that capitalises the first letter is fine. Tick
**Keep me logged in** on a device you trust.

After five failed attempts in a minute, logins from your address are paused for a minute.

### First login

Accounts are created with a temporary password. On your first login you'll be taken to
**Change password** and can't go anywhere else until you pick a new one. The new password
must be different from the temporary one and at least 8 characters.

### Forgotten password

Swag doesn't send email. Ask an administrator to set a new temporary password for you.

## Pages

**Pages** (the home page) lists your pages, newest first, 20 per screen. Tap a title to
read it.

On a page you can:

- **Edit** the title and Markdown directly.
- **Delete** it. You'll be asked to confirm.

Pages are created by AI assistants and scripts through the API, not from the website. See
[AI assistants](ai-assistants.md) and [REST API](api.md).

You only ever see your own pages. Administrators can't see other users' pages either.

## API tokens

**API tokens** is where you give an AI assistant access to your board.

1. Type a name that says where the token will be used, such as `Laptop agent` or `Backup script`,
   then press **Create token**.
2. The token is shown **once**. Press **Copy**. On a plain-HTTP connection the copy button
   selects the text so you can copy it yourself.
3. Give the token to your assistant (see [AI assistants](ai-assistants.md)).

The list shows each token's name, creation date and when it was last used. **Revoke** a
token to cut off whatever is using it straight away. Your other tokens keep working.

A token can do anything to your pages: create, edit and delete. It can't touch your
account or other users' pages. Treat it like a password.

## Account

Click your name in the navigation bar to change your name, email or password.

## Managing users (administrators)

Administrators see a **Users** link.

| Task | How |
|---|---|
| Add someone | **New user**. Enter name, email and a temporary password, and optionally tick **Administrator**. Give them the password yourself. They must change it at first login. |
| Reset a password | **Set password** next to the user. They must change it at their next login. |
| Remove someone | **Delete**. This also deletes all their pages and API tokens and can't be undone. |

You can't delete your own account, so there's always at least one administrator.

The very first administrator is created on the server with
`php artisan swag:create-admin` (see [Getting started](getting-started.md) or
[Deploying to cPanel](deployment-cpanel.md)).
