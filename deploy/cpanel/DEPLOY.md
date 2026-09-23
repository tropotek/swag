# Deploying noteBoard to cPanel

The bundle unpacks into two sibling folders in your cPanel home directory:
`~/noteboard-app/` (code, database, never web-served) and `~/public_html/`
(web root).

## Before the first deploy

In cPanel, check that:
- PHP (MultiPHP Manager) is set to 8.5.
- The `pdo_sqlite` extension is enabled (Select PHP Version → Extensions).
- Terminal (or SSH) is available.
- In Terminal, `php -v` shows the same version as the web PHP. If not, use the
  matching binary, e.g. `/usr/local/bin/ea-php85`, for every `php` command below.
- AutoSSL has issued a certificate for the domain. The `.htaccess` forces HTTPS.

Back up anything already in `public_html/`, because extracting the bundle overwrites matching files.

## Build (on the dev machine)

    git status              # must be clean
    deploy/build-cpanel.sh  # → dist/noteboard-cpanel.tar.gz

## First deploy

1. Upload `noteboard-cpanel.tar.gz` to your home directory (File Manager) and extract it there.
   File Manager hides dotfiles by default. Turn on Settings → "Show Hidden Files" to see
   `.env` and `public_html/.htaccess`.
2. In Terminal:

       cd ~/noteboard-app
       cp .env.example .env

3. Edit `~/noteboard-app/.env`. Laravel reads this file itself at boot, and
   `config:cache` in step 4 then bakes the values into `bootstrap/cache/config.php`.
   Re-run `config:cache` after every `.env` edit.

       APP_NAME=noteBoard
       APP_ENV=production
       APP_DEBUG=false
       APP_URL=https://your-domain.example
       APP_TIMEZONE=Your/Timezone
       DB_CONNECTION=sqlite
       SESSION_DRIVER=database
       SESSION_SECURE_COOKIE=true
       LOG_LEVEL=warning

4. In Terminal:

       touch database/database.sqlite
       php artisan key:generate --force
       php artisan migrate --force
       php artisan noteboard:create-admin
       php artisan config:cache && php artisan route:cache && php artisan view:cache

## Updating

1. Build and upload a new bundle, then extract it over the existing folders.
   `.env` and `database/database.sqlite` aren't in the bundle, so they survive.
2. In Terminal:

       cd ~/noteboard-app
       php artisan migrate --force
       php artisan config:cache && php artisan route:cache && php artisan view:cache

## Check it works

- `https://your-domain.example/login` loads with styling.
- Create a token under **API tokens**, then:

      curl -s https://your-domain.example/api/pages -H "Authorization: Bearer TOKEN"

  This should return JSON with a `data` array. A 401 with a valid token means the host is stripping the
  `Authorization` header. Check that `public_html/.htaccess` is the one from the bundle.

## If something breaks

- 500 on every page: check `~/noteboard-app/storage/logs/laravel.log`.
  "Vite manifest not found" means `public_html/build/` is missing or `public_html/index.php` isn't the bundle's.
- "attempt to write a readonly database": the `database/` folder (not just the file) must be writable.
  Run `chmod 755 ~/noteboard-app/database` and `chmod 644 ~/noteboard-app/database/database.sqlite`.
