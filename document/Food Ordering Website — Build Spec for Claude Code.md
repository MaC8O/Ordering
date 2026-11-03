# Food Ordering Website — Build Spec for Claude Code

Nov 3, 2026 · @Matthew Morgan

## 1. Overview

A single-restaurant food ordering website for an AIU campus audience. Customers browse the menu, add items to a cart, enter their dorm and contact details, and submit an order. Payment is either a QR-code bank transfer (customer uploads a screenshot of the transfer) or cash on delivery. The restaurant decides which days of the week ordering is open, and can close ordering any time.

The site is mobile-first, loads fast, and needs no customer login. One admin account manages everything.

## 2. Users and roles

Two kinds of user. No customer signup — orders are placed as a guest with a phone number for contact.

| Role | What they can do |
| --- | --- |
| Customer (guest) | Browse menu, view item details, add to cart, checkout, upload payment screenshot, view order confirmation with order number |
| Admin (restaurant) | Log in, add/edit/delete menu items and categories, upload QR image, set weekly open days and cutoff times, view and update orders, mark payments verified, see today's sales |

## 3. Feature list

MUST — ship these for the project to count:

- Menu grid with categories, item photo, name, price, short description
- Item detail view with quantity picker and optional note
- Cart (add, remove, change quantity, running total)
- Checkout form (customer details, delivery address, payment method, order notes)
- QR payment flow: show QR image, let customer upload transfer screenshot
- Cash on delivery option
- Order confirmation page with a unique order number
- Admin login
- Admin menu management (categories + items, with image upload)
- Admin order list with status update (Pending → Confirmed → Preparing → Out for delivery → Completed, plus Cancelled)
- Admin ordering schedule — pick which weekdays are open, set a daily cutoff time
- A closed-today banner on the storefront when ordering is off, with the next open day shown

SHOULD — add if time allows:

- Admin dashboard with today's order count, revenue, and pending-payment count
- Daily order number reset (ORD-20261103-001)
- Simple search box on the menu
- Soft-delete on menu items (hide, don't lose history)
- Printable order ticket for the kitchen

NICE — only if everything else is done:

- Customer order lookup by order number + phone
- SMS or email receipt
- Popular items section
- Promo codes

## 4. Site map and navigation

Keep the top navigation to four items: Menu · Cart · My Order · About. The cart shows a live item count badge.

Customer pages:

1. **Home / Menu** (`/`) — hero image, open/closed banner, category tabs or chips, item grid
2. **Item detail** (`/item/{id}`) — larger photo, description, quantity picker, note field, Add to cart
3. **Cart** (`/cart`) — line items, quantities, subtotal, Checkout button
4. **Checkout** (`/checkout`) — contact + delivery form, payment choice (QR or Cash), QR image + upload field when QR chosen, Place order button
5. **Order confirmation** (`/order/{order_number}`) — order number, items, total, payment status, delivery address, support contact
6. **My order lookup** (`/my-order`) — enter order number + phone to view status (SHOULD feature)
7. **About / Contact** (`/about`) — hours, phone, location, FAQ

Admin pages (behind `/admin/login`):

1. **Dashboard** (`/admin`) — today's orders, revenue, pending payments
2. **Orders** (`/admin/orders`) — list with filters by status and date, click into one to update status
3. **Order detail** (`/admin/orders/{id}`) — full details, verify payment screenshot, change status, print ticket
4. **Menu** (`/admin/menu`) — categories and items with CRUD
5. **Settings** (`/admin/settings`) — weekly open days, daily cutoff time, QR image, restaurant info

## 5. Data model

Seven tables. Prices stored in the smallest currency unit (integer) to avoid float errors.

| Table | Key fields |
| --- | --- |
| `categories` | id, name, display\_order, is\_active |
| `menu_items` | id, category\_id (FK), name, description, price (int, minor units), image\_path, is\_available, display\_order, created\_at |
| `orders` | id, order\_number (unique, human-readable), customer\_name, customer\_phone, delivery\_dorm, delivery\_room, delivery\_notes, payment\_method (enum: qr, cash), payment\_screenshot\_path (nullable), payment\_status (enum: pending, verified, failed), order\_status (enum: pending, confirmed, preparing, out\_for\_delivery, completed, cancelled), subtotal, total, notes, created\_at, updated\_at |
| `order_items` | id, order\_id (FK), menu\_item\_id (FK), item\_name\_snapshot, item\_price\_snapshot, quantity, line\_total |
| `settings` | id (singleton), restaurant\_name, phone, qr\_image\_path, cutoff\_minutes\_before\_close, default\_delivery\_fee |
| `schedule` | weekday (0–6), is\_open, open\_time, close\_time |
| `admins` | id, username, password\_hash, created\_at |

Key rules:

- `order_items` stores a **snapshot** of name and price at order time. If an admin later changes a menu item's price, old orders keep showing what the customer actually paid.
- `menu_items.is_available = false` hides an item from the storefront without deleting it (preserves order history).
- `order_number` format: `ORD-YYYYMMDD-NNN` (e.g. `ORD-20261103-007`), generated server-side.
- Images are stored as file paths (e.g. `/uploads/menu/pizza.jpg`), not as blobs in the database.

## 6. Order flow (end to end)

1. Customer lands on the menu. If ordering is closed today, a banner says so and the Add-to-cart buttons are disabled.
2. Customer taps an item → item detail → picks quantity → Add to cart.
3. Cart icon badge updates. Cart contents are kept in `localStorage` so they survive refresh.
4. Customer opens the cart, reviews, taps Checkout.
5. Checkout form appears: name, phone, dorm, room, notes, payment method.
6. If QR is picked, the QR image appears below with a note like "Scan and transfer, then upload the screenshot." A file input accepts the screenshot (image only, max 5 MB).
7. If Cash is picked, no upload is needed — a reminder shows the total they will need in cash.
8. Customer taps Place order. Server validates, creates the order with status `pending`, saves the screenshot (if any), returns the order number.
9. Customer lands on the confirmation page showing the order number, items, total, payment status, and the restaurant's phone number.
10. Admin sees the order in the dashboard. For QR orders they open the screenshot, confirm the transfer in their bank app, and mark payment Verified. For cash they just move the status forward.
11. Admin updates status as the order progresses. The customer can refresh the order lookup page to see progress.

## 7. Payment

Two methods, picked by the customer on checkout. Nothing is auto-verified — the admin manually confirms every payment.

**QR code (bank transfer)**

- Admin uploads one QR image in Settings; it is shown on checkout when QR is selected.
- Below the QR, the customer sees: the exact total and the restaurant's account name.
- Customer transfers in their bank app, then uploads a screenshot of the transfer confirmation.
- Server saves the file as `/uploads/payments/{order_number}.{ext}`, validates it is an image, rejects files over 5 MB.
- Order is created with `payment_status = pending`, `order_status = pending`.
- Admin opens the order, taps the screenshot thumbnail to view it full-size, confirms the amount in their bank app, then sets `payment_status = verified` and moves `order_status` to `confirmed`.

**Cash on delivery**

- No upload. Order is created with `payment_status = pending`, `order_status = pending`.
- Confirmation page tells the customer the cash amount to prepare.
- Admin can confirm the order immediately (set `order_status = confirmed`); `payment_status` moves to `verified` when the delivery person collects.

**Rejected / mismatched payment**

- If the screenshot doesn't match or is unreadable, admin sets `payment_status = failed` and `order_status = cancelled`, with a short note the customer sees on the lookup page.

## 8. Delivery (AIU campus)

All deliveries are on-campus, so no map or geocoding is needed. The checkout form collects enough to find the person on campus.

| Field | Type | Notes |
| --- | --- | --- |
| Full name | text, required | Minimum 2 characters |
| Phone number | text, required | Validate as digits + optional `+`, 7–15 chars |
| Dorm / building | dropdown, required | Admin-editable list — pre-fill with the actual AIU dorms and buildings |
| Room number | text, required | Short free text (e.g. `204B`) |
| Delivery notes | textarea, optional | "Call when you arrive", "Leave at reception", etc. |
| Preferred delivery time | time picker, optional | Within today's open hours only |

Validation rules:

- All required fields filled before Place order is enabled.
- Phone number pattern checked client-side and re-checked server-side.
- Dorm must be one of the admin's current list — a free-text dorm is rejected to avoid typos.
- If preferred time is before now or after today's close time, show an inline error.

The delivery address shows on the order confirmation and on the admin's printable ticket.

## 9. Admin features

**Login**

- Single admin account seeded on install (`admin` + a password set via environment variable or first-run prompt).
- Session cookie or JWT; auto-logout after 24 hours idle.
- All `/admin/*` routes behind an auth middleware.

**Dashboard** (`/admin`)

- Four tiles at the top: Today's orders, Today's revenue, Pending payment, Preparing.
- A list of today's orders below, newest first, each a tap-through to the order detail.

**Orders** (`/admin/orders`)

- Filter by status, by date range, and by payment method.
- Each row shows order number, time, customer, items count, total, payment status, order status.
- Click a row to open the detail view with the full item list, payment screenshot (if any), status dropdown, Verify payment button, Print ticket button, Cancel order button.

**Menu management** (`/admin/menu`)

- Categories tab: add, rename, reorder (drag), toggle active.
- Items tab: add, edit, delete (soft), toggle availability, upload or replace photo.
- Form fields per item: name, category, price, description, photo, availability.

**Settings** (`/admin/settings`)

- Restaurant name, phone, address.
- Upload or replace the QR image.
- Dorm list (add/remove items).
- Weekly schedule (see next section).

## 10. Open / close scheduling

The restaurant decides when ordering is accepted. Two layers:

**Weekly schedule** — a row per weekday in the `schedule` table. For each day: open yes/no, open time, close time, and a last-order cutoff (e.g. close at 21:00, stop accepting orders at 20:30).

**Manual override** — a single switch on the dashboard: "Accepting orders now" (on/off). Flipping it off closes the storefront immediately regardless of the weekly schedule. Flipping it on only works during a scheduled open window.

**Storefront behavior when closed**

- Menu is still visible for browsing.
- Add-to-cart buttons are disabled and show a tooltip explaining why.
- A banner at the top says: "We're closed right now. Next opening: Tuesday at 10:00."
- Checkout route rejects any submission with a clear message and sends the customer back to the cart.

**Server rule** — ordering open is calculated server-side on every checkout, never trusted from the client. The logic is: `manual_override == on` AND `today.is_open == true` AND `now` is between `open_time` and `close_time - cutoff_minutes`.

## 11. Design and UX

Aim: looks appetizing, loads fast, works on a phone one-handed. Students will mostly order from a phone, so build mobile-first and let it scale up to desktop.

**Visual direction**

- Warm, food-friendly palette: a strong accent color (deep orange, red, or green), off-white background, dark neutral text. Avoid pure black.
- Large, high-quality food photos are the hero of every page. Use a 4:3 or 1:1 crop consistently.
- Rounded corners (12–16 px), subtle shadows, generous white space.
- One modern sans-serif font (Inter, Poppins, or Plus Jakarta Sans). Two weights only: regular and semibold.
- Clear price typography — bigger than the description.

**Layout patterns**

- Sticky top nav with logo, menu, cart icon with item count.
- Menu as a card grid: 1 column on phone, 2 on tablet, 3–4 on desktop.
- Category chips above the grid; tapping a chip filters without reloading.
- Cart as a bottom sheet on phone, a side drawer on desktop.
- Checkout as a single scrollable page, not a multi-step wizard — fewer taps, faster submit.

**Micro-interactions**

- Add-to-cart button briefly animates and the cart badge bumps up.
- Toast at the bottom of the screen confirms every action.
- Skeleton loaders instead of spinners while images load.
- Disabled buttons explain why in a tooltip.

**Accessibility basics**

- Color contrast ≥ 4.5:1 for text.
- All form inputs have labels, not just placeholders.
- Images have alt text (the item name is fine).

## 12. Tech stack and project structure

Two good options — pick whichever Claude Code is already set up for, or whichever you know best. Don't learn a new framework for this project.

**Option A — Laravel (PHP) + Blade + Tailwind CSS + MySQL/SQLite**

- Big win: Laravel's auth scaffolding, migrations, Eloquent ORM, and file uploads come out of the box.
- Blade is fast to write and easy for an examiner to read.
- Use Livewire or Alpine.js for small interactive bits (cart badge, add-to-cart animation).

**Option B — Next.js (App Router) + Tailwind + Prisma + SQLite**

- Big win: one language (TypeScript), file-based routing, instant deploy to Vercel.
- Server actions handle form submits with no separate API layer.
- Use NextAuth for the admin login.

**Shared choices**

- Tailwind CSS for all styling.
- SQLite for local dev (zero setup); switch to MySQL/Postgres for deploy if required.
- Local file storage under `/public/uploads/` for images and screenshots (fine for a school project).
- `.env` for secrets (DB URL, admin password, app URL).

**Folder structure (example, Next.js)**

```
/app
  /(storefront)
    page.tsx            // menu
    /item/[id]/page.tsx
    /cart/page.tsx
    /checkout/page.tsx
    /order/[number]/page.tsx
  /admin
    layout.tsx          // auth guard
    page.tsx            // dashboard
    /orders
    /menu
    /settings
  /api                  // only if needed
/components
/lib                     // db, auth, utils
/prisma
  schema.prisma
/public
  /uploads
    /menu
    /payments
    qr.png
```

## 13. User-provided assets

You said you'll upload the menu and the QR code. Put them where Claude Code can find them:

| Asset | Where to put it | Used by |
| --- | --- | --- |
| Menu photos (one per item) | `/public/uploads/menu/{item-slug}.jpg` | Seeded into `menu_items.image_path` |
| QR code image | `/public/uploads/qr.png` | Shown on checkout when QR payment is picked |
| Menu data (names, prices, categories) | `/data/menu.csv` or `/data/menu.json` | Seed script loads into `categories` + `menu_items` |
| Logo (if any) | `/public/logo.png` | Top nav + favicon |

If the menu you have is a photo of a printed menu, retype it into `menu.csv` with columns `category, name, description, price, image_filename`. Claude Code can generate the seed script from that CSV.

Image specs:

- Menu photos: square (1:1), 1200×1200 px, under 500 KB each. Compress with `squoosh.app` or `tinypng.com` before uploading.
- QR image: square, at least 600×600 px, PNG.

## 14. Build order (milestones for Claude Code)

Build in this order so you always have a running app. Commit to git after each milestone. Ask Claude Code to explain anything you don't follow before moving on.

1. **M1 — Scaffold and database.** Fresh project, Tailwind set up, database migrations for all seven tables, admin seeded, a landing page that says "Hello".
2. **M2 — Menu display.** Seed the menu from your CSV, build the home page grid, category chips, item detail page. Pure read-only — no cart yet.
3. **M3 — Cart.** `localStorage`-based cart, add/remove/quantity, cart page with totals, cart badge in nav.
4. **M4 — Checkout and order creation.** Checkout form, server-side validation, order + order\_items creation, order number generation, confirmation page.
5. **M5 — Admin auth and orders list.** Login page, auth middleware, orders list, order detail, status change, cancel.
6. **M6 — Admin menu management.** Category CRUD, item CRUD, image upload, availability toggle.
7. **M7 — Settings and schedule.** Weekly schedule, manual override, QR upload, dorm list, cutoff logic enforced on checkout.
8. **M8 — Payment screenshot upload.** QR flow on checkout, file upload with size and type checks, screenshot preview on admin order detail.
9. **M9 — Dashboard and polish.** Today's stats tiles, print ticket, closed-today banner, empty states, 404 page.
10. **M10 — Deploy.** Push to GitHub, deploy (Vercel, Railway, or your own server), set production env vars, test end-to-end on a phone.

## 15. Acceptance tests

Run through each of these on a phone and a laptop before you submit. Every one should pass.

- [ ] I can see the full menu with photos and prices on first load.
- [ ] Tapping a category chip filters the grid.
- [ ] Opening an item shows its details, I can set quantity 1–20, and Add to cart updates the badge.
- [ ] The cart survives a page refresh.
- [ ] I can change quantity and remove items in the cart.
- [ ] Checkout rejects the submit if any required field is blank or the phone is malformed.
- [ ] Choosing QR shows the QR image and requires a screenshot upload.
- [ ] Choosing Cash does not require a screenshot.
- [ ] After submitting, I land on a confirmation page with a unique order number.
- [ ] The order appears in the admin dashboard within 2 seconds.
- [ ] Admin can open the screenshot, mark payment verified, and advance the status.
- [ ] Admin turns off today's open day; the storefront immediately shows the closed banner and disables Add to cart.
- [ ] Admin re-opens the day; the storefront reflects it on refresh.
- [ ] Admin changes a menu item's price; existing orders still show the price the customer actually paid.
- [ ] An unauthenticated user visiting `/admin` is sent to login.
- [ ] The whole flow works on a phone-sized viewport without horizontal scroll.

## 16. Out of scope and future improvements

Intentionally left out of v1 to keep the project finishable in a week:

- Customer accounts, saved addresses, order history across devices
- Real payment gateway integration (Stripe, PayPal, local bank API)
- Automatic QR amount verification
- SMS or email notifications
- Delivery driver app and live tracking
- Multiple restaurants or branches
- Promo codes, loyalty points, referrals
- Reviews and ratings
- Full-text search and recommendations
- Multi-language UI

Good candidates to add after submission, in rough order of effort vs value:

1. SMS confirmation to the customer (Twilio or a local provider).
2. Customer-visible status updates on the lookup page without refresh (polling every 10 s).
3. Admin mobile view, so staff can run everything from a phone during a rush.
4. Export orders to CSV for weekly reporting.
5. A real payment gateway when the restaurant is ready.
