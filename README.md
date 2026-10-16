# Pehchanvala — local Laravel CMS

Laravel 12, PHP 8.2+, MySQL-compatible MariaDB, and Node/Vite. The original HTML design is preserved, with database-backed content and a separate authenticated agency CMS.

## Open locally

- Website: <http://127.0.0.1:8000>
- CMS: <http://127.0.0.1:8000/admin>
- Generated local administrator credentials: `.local-admin.txt` (ignored; do not share).
- Change the generated password in **Settings** after signing in.
- To restart, run `./Start-Local.ps1` in PowerShell. It starts the local database, website and queue worker in hidden windows. The built assets work without a running Vite server.

The database is isolated in `storage/mysql` on loopback port **3307** using XAMPP's MariaDB 10.4. It does not modify the existing XAMPP database directory or install a Windows service. The app has a dedicated database user; the isolated server's generated root password stays inside the ignored database directory. `.env` contains only this application's connection credentials.

## What works

- Admin sign-in/out, password changes, route authorization, CSRF protection and login throttling. No public registration.
- Property creation/editing, draft/publish, featured status, archive, local photograph uploads and optional HTTPS photo URLs.
- Rent, sale, both, PG per-bed rent, sharing, meals, deposit, next available date, amenities and map location.
- Database-backed public property search and desktop/mobile media views. Uploaded images are served by a constrained media route, so no Windows symlink is required.
- Property location picker: search Pune buildings/landmarks, select a result, click the map or drag the pin, then save. Exact latitude/longitude are stored in MySQL and used by the public Google Maps view and directions link. Existing address-only records retain their approximate map until a pin is selected. The key-free editor uses Leaflet/OpenStreetMap tiles and Photon geocoding; searches run through a protected, rate-limited and cached CMS endpoint. Internet access is required for tiles/search. No Google Maps API key is configured.
- Journal editing, cover uploads, draft/publish and unpublish.
- Enquiry persistence, follow-up status and private notes; queued agency and customer confirmations with retry of unsent emails.
- Agency email, Google Analytics ID and policy editing. Optional analytics is consent-based and disabled without a measurement ID.
- Agency-issued payment references, payer/amount/purpose matching, server-created Razorpay orders, session-bound signature verification, API verification of captured amounts/currency, signed webhook reconciliation and idempotent captured-payment notifications.

Six illustrative properties and three sample articles were seeded. Replace or unpublish them before real use. One clearly marked closed local QA enquiry is retained as evidence of the end-to-end form test. No external mail was sent.

## Mail

Local default is `MAIL_MAILER=log`. Both confirmations are written to `storage/logs/laravel.log`; inbox delivery is **not** configured. Set your SMTP transport in `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=your-provider-host
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=info@pehchanvala.com
MAIL_FROM_NAME=Pehchanvala
```

Use your provider's actual SMTP settings. After changing environment values, run `php artisan config:clear` and `php artisan queue:restart`. `Start-Local.ps1` can then restart the worker. Inspect failures with `php artisan queue:failed`; retry a failed job with `php artisan queue:retry <id>`. A transport accepting a message does not prove inbox delivery.

## Razorpay

No Razorpay account or keys were supplied, so payments remain disabled. Start with test keys:

```dotenv
PAYMENTS_ENABLED=true
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
```

Also supply all three final policy texts in CMS Settings and approve them for payment collection. Create a payment reference in **Payments** and share its purpose, exact amount, reference and applicable written terms with the customer. Checkout rejects altered amounts/purposes and expired/paid/disabled references. A pending order is reused only by the same browser session. A different session is blocked to prevent accidental duplicate payment; reconcile it with the agency before a new attempt. There is no automated refund action; gateway refunds and disputes require agency handling.

Configure a signed Razorpay webhook to `/payments/webhook` for `payment.captured` and `order.paid`. Localhost is not publicly reachable by Razorpay; real webhook testing needs a user-approved public development endpoint. Only the webhook route is exempt from CSRF; it requires an HMAC signature. Payment secrets never enter the frontend. API/webhook behavior is tested with fakes; no live charge or real gateway test transaction was performed.

## Development

```powershell
composer install
npm install
npm run dev
```

`npm run build` produces local Vite assets. `php artisan serve --host=127.0.0.1 --port=8000` starts the website. `php artisan queue:work --tries=5` processes email jobs.

On a clean checkout, copy `.env.example` to `.env`, supply a new empty MySQL database and credentials, run `php artisan key:generate`, then `php artisan migrate --seed` and `php artisan agency:admin your-email@example.com`. The command prompts for a password. `--local` creates a random password in the ignored local credentials file. Do not run migrations against an unrelated existing database.

The existing machine's isolated database can be provisioned from XAMPP with `mysql_install_db.exe --datadir=<project>/storage/mysql --port=3307`. Start that server bound to 127.0.0.1, then `php scripts/setup-local.php` configures its dedicated database/user. That script is only for this known isolated instance, not an existing shared database.

## Checks

```powershell
php vendor/phpunit/phpunit/phpunit --testdox
npm run build
php artisan view:cache
node --test tests/js/property-map.test.mjs
```

The tests use a forced, isolated SQLite memory database; they never reset the local MySQL content. Coverage includes authentication/authorization, publishing visibility, uploads, blog cover edits, enquiry persistence/email retries, amount tampering, signed/idempotent webhook capture and cross-session verification rejection.

The public frontend currently retains the preview's hash routes. A public SEO launch should replace these with prerendered/Blade property and journal URLs, and approved listing metadata. This delivery is a local CMS application; the earlier hosted HTML preview and the existing pehchanvala.com were not changed.

Stock photos, maps and fonts use external providers. Replace representative photography with approved assets and supply final business/legal policies before launch. Do not expose the local debug server or database to the internet.

