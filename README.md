# Campus Kitchen — Food Ordering Website

A single-restaurant, mobile-first food ordering site for an AIU campus audience, built with
**Laravel 13 + Blade + Tailwind CSS 4 + Alpine.js** (MySQL/SQLite).

Customers browse the menu, build a cart (kept in `localStorage`), and check out as a guest — no
login. Payment is either a **QR bank transfer** (upload a screenshot) or **cash on delivery**.
One admin account manages the menu, orders, schedule, and settings. Ordering opens and closes on a
weekly schedule with a manual master switch, enforced server-side.

---

## Requirements

- PHP 8.3+
- Composer 2
- Node.js 18+ and npm
- MySQL (or SQLite)

## Setup

```sh
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
# edit .env — set DB_* and, optionally, ADMIN_USERNAME / ADMIN_PASSWORD

# 3. Database (creates tables and seeds menu, settings, schedule, admin)
php artisan migrate:fresh --seed

# 4. Build front-end assets
npm run build        # or: npm run dev  (for hot reload while developing)

# 5. Run
php artisan serve
```

Then open http://127.0.0.1:8000.

### Admin access

- URL: `/admin/login`
- Username / password come from `.env` (`ADMIN_USERNAME`, `ADMIN_PASSWORD`), defaulting to
  `admin` / `password`. **Change the password before any real deployment.**

## Key points

- **Prices** are stored as integers in the minor unit (sen/cents) to avoid float errors.
  Display currency is set in `config/ordering.php` (`ORDERING_CURRENCY`, default `฿` / Thai Baht).
- **Timezone** defaults to `Asia/Kuala_Lumpur` (`APP_TIMEZONE` in `.env`). Open/close is computed
  in this timezone.
- **Order numbers** are `ORD-YYYYMMDD-NNN`, generated server-side and reset daily.
- **Order history is preserved**: `order_items` snapshots each item's name and price at order time,
  and menu items are soft-deleted / hidden rather than destroyed.
- **Open/close rule** (always server-side, never trusted from the client):
  `accepting_orders` (manual switch) **AND** today's schedule `is_open` **AND**
  `open_time ≤ now ≤ close_time − cutoff_minutes`.

## SMS notifications (Twilio)

When an admin moves an order to **Out for delivery**, the customer is texted a
"your order is on the way" message with the arrival ETA and a tracking link.

- Configure Twilio in `.env`: `TWILIO_SID`, `TWILIO_TOKEN`, `TWILIO_FROM`
  (a Twilio number or Messaging Service SID). `SMS_DEFAULT_COUNTRY_CODE`
  (default `+66`) is prepended to local numbers when formatting to E.164.
- **If Twilio is not configured, the message is written to the log instead of
  being sent** (`storage/logs/laravel.log`, tagged `[SMS stub]`), so the feature
  works in demo mode and starts sending for real the moment credentials are added.
- The send is wrapped in a try/catch — a Twilio failure never blocks the status
  update; the admin sees the outcome in the success message.

## Uploads

Stored under `public/uploads/`:

- `public/uploads/menu/` — menu item photos
- `public/uploads/payments/` — payment screenshots (`{order_number}.{ext}`)
- `public/uploads/qr.*` — the payment QR image (set in Admin → Settings)

## Seed data

- Menu is seeded from `data/menu.json` (edit this file and re-run the seeder to change the menu).
  If a photo named in `image_filename` exists under `public/uploads/menu/`, it is linked; otherwise
  a styled placeholder is shown. Replace photos and re-seed, or upload via Admin → Menu.
- A QR image is **not** bundled — upload one in Admin → Settings to enable the QR payment flow.

## Project layout

```
app/
  Http/Controllers/        storefront controllers
  Http/Controllers/Admin/  admin controllers
  Http/Requests/           CheckoutRequest (validation)
  Models/                  Category, MenuItem, Order, OrderItem, Setting, Schedule, Admin
  Services/                StoreStatus (open/close), OrderNumberGenerator
resources/views/
  layouts/                 storefront + admin layouts
  menu/ cart/ checkout/ order/ my-order/ about  (storefront)
  admin/                   dashboard, orders, menu, settings, auth
database/
  migrations/              seven domain tables
  seeders/                 Admin, Settings, Schedule, Menu
data/menu.json             editable menu source
```

## Acceptance tests

The build satisfies the acceptance checklist in the build spec (menu + photos, category filter,
item detail with 1–20 quantity, persistent cart, checkout validation, QR vs cash flows, unique
order number, admin dashboard, payment verification + status advance, closed-day banner and disabled
add-to-cart, immutable price snapshots on past orders, and `/admin` auth redirect).
