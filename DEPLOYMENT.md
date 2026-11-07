# Deployment — Laravel + MySQL on Hostinger shared hosting

Step-by-step for deploying **Front Gate Restaurant / GateGo** to a Hostinger
Premium (shared) plan. The app is **Laravel 13, PHP 8.3+, MySQL**, with
frontend assets built by Vite.

> Shared hosting has **no Node.js**, so compiled assets (`public/build/`) are
> committed to the repo and ship with it. After any CSS/JS/Blade change, run
> `npm run build` locally and commit `public/build/` before deploying.

---

## 1. hPanel — PHP & database

1. **PHP version:** *Advanced → PHP Configuration* → select **PHP 8.3** (or 8.4).
   Under *PHP options*, set `upload_max_filesize` and `post_max_size` to **8M+**
   (payment-screenshot uploads allow 5 MB).
2. **Database:** *Databases → Management* → create a MySQL database + user.
   Note the **DB name, user, password, host** (host is usually `localhost`).

## 2. Build assets locally (your PC)

```bash
npm run build       # outputs public/build/ (already committed)
```

## 3. Get the code onto the server

**Git (recommended):** hPanel → *Advanced → GIT* → deploy
`https://github.com/MaC8O/Ordering` (branch `main`) into
`~/domains/<your-domain>/laravel` (i.e. **outside** `public_html`).

**Or manual upload:** zip the project *excluding* `node_modules`, `vendor`,
`.env`; upload + extract to the same folder. Keep `public/build/` in the zip.

## 4. Point the domain at `/public`

hPanel → *Website → <domain> → document root* →
`/domains/<your-domain>/laravel/public`

If the primary domain's root can't be changed, SSH and symlink instead:
```bash
rm -rf ~/public_html
ln -s ~/domains/<your-domain>/laravel/public ~/public_html
```

## 5. SSH — install & initialise

hPanel → *Advanced → SSH Access*, then:

```bash
cd ~/domains/<your-domain>/laravel

composer install --no-dev --optimize-autoloader
cp .env.production.example .env          # then edit (see below)
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=SettingsSeeder --force
# Optional — preload the menu & drinks:
# php artisan db:seed --class=ThaiMenuSeeder --force
# php artisan db:seed --class=DrinkSeeder --force
php artisan storage:link
chmod -R 775 storage bootstrap/cache
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### `.env` values to fill
Start from `.env.production.example`. Minimum to change:

```env
APP_URL=https://<your-domain>
APP_KEY=                      # key:generate fills this
DB_DATABASE=<db name>
DB_USERNAME=<db user>
DB_PASSWORD=<db password>
SESSION_DOMAIN=<your-domain>
ADMIN_PASSWORD=<strong password>   # used by the admin seeder
```

Keep `APP_ENV=production`, `APP_DEBUG=false`, `QUEUE_CONNECTION=sync`,
`SESSION_SECURE_COOKIE=true`.

## 6. SSL & final checks

- hPanel → *Security → SSL* → install free SSL, force HTTPS.
- Open the site; go to **`/admin`** (seeded admin login) and upload the
  **PromptPay QR** under *Settings* if the committed one isn't current.
- Place a test order end-to-end (cart → checkout → receipt upload → admin
  verify → status updates).

---

## After each future deploy

```bash
git pull                 # on the server (if deployed via Git)
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Blank page / HTTP 500 | Document root not on `/public`, or `storage`/`bootstrap/cache` not writable (`chmod -R 775`). Temporarily set `APP_DEBUG=true` to read the error, then revert. |
| No styling | `public/build/` wasn't uploaded — rebuild (`npm run build`) and commit it. |
| "No application encryption key" | Run `php artisan key:generate`. |
| 404 on every route but `/` | `mod_rewrite` / `public/.htaccess` not honoured — ensure the doc root is `/public` and `.htaccess` was uploaded. |
| Config changes not taking effect | `php artisan config:clear` then re-cache. |

## Notes specific to this app

- **Delivery fee** is per food item with an admin-set cap (*Settings*). Needs the
  `delivery_fee_cap` migration (included).
- **Payment** is manual QR-transfer + screenshot; no payment gateway to configure.
- **Queue** is `sync` (no worker/cron needed) and there are no scheduled tasks.
- **Mail** defaults to `log`; set SMTP in `.env` only if you want email.
- **SMS** (order "on the way") is stubbed unless `TWILIO_*` is set in `.env`.
- **Customer payment screenshots** live in `public/uploads/payments/`, which is
  git-ignored and `.htaccess`-protected (no script execution) — never commit them.
