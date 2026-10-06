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

**Pages** (the home page) lists your pages, most recently updated first, 50 per screen (the dropdown also offers 20, 100 and 200). Use the search box in the top bar to find pages by words in the title or body; results are ordered best match first. Editing a page moves it back to the top. Tap a title to
read it.

On a page you can:

- **Edit** the title and Markdown directly.
- **Delete** it. You'll be asked to confirm.

**New page** on the Pages screen opens a blank title and Markdown form. Pages are also created
by AI assistants and scripts through the API — see [AI assistants](ai-assistants.md) and
[REST API](api.md).

You only ever see your own pages. Administrators can't see other users' pages either.

## Images and files

While editing a page, **Attach file** uploads a file, or you can drag one onto the Markdown box
or paste one straight from the clipboard. The Markdown is inserted where your cursor is.

Images appear in the page. Audio and video play in a new tab. Everything else, PDFs included,
becomes a link that downloads. Uploads are at most 25 MB, and programs and scripts (`.exe`,
`.bat`, `.sh`, `.js`, `.php` and similar) are refused — put one in a zip file and upload that.

Your files are private. A link to one only works while you're logged in, so sending it to
someone else shows them a login screen, not your file.

### Laying an image out

Images already shrink to fit the screen, so they never overflow on a phone. To do more, put a
class in braces. On the line **after** an image it styles the whole line; directly **after the
link** it styles the image itself.

```markdown
![The ute](/media/abc/ute.jpg)
{.text-center}

![The ute](/media/abc/ute.jpg){.half}

![The ute](/media/abc/ute.jpg){.rounded .border}
```

| Class | What it does |
|---|---|
| `{.text-center}` | Centres the image on its own line. Put it on the line after. |
| `{.half}` | Half width on a tablet or desktop, full width on a phone. |
| `{.rounded}`, `{.border}`, `{.shadow}` | Rounded corners, a border, a drop shadow. |

Use `{.half}` rather than Bootstrap's `{.w-50}`: `w-50` is half width at *every* screen size, so
it shrinks to a thumbnail on a phone.

Only `class` works. `style="..."` is stripped, and raw HTML such as `<center>` or `<img>` shows
as literal text.

Photos straight from a camera are several megabytes, and Swag stores what you give it. If a page
feels slow to load on your phone, resize the photo before uploading it.

## API tokens

**API tokens** (in the menu under your name) is where you give an AI assistant access to your
board.

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

Your name in the navigation bar opens a menu with **Account** — where you change your name,
email or password — **API tokens**, **Users** for administrators, and **Log out**.

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
