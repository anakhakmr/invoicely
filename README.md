# Invoicely

An invoicing application built on the TALL stack (Tailwind, Alpine.js, Laravel, Livewire) with a Filament admin panel and Stripe payments.

Staff manage clients and invoices through an admin panel; clients log into their own portal to view and pay invoices via Stripe. See `CLAUDE.md` for the architecture overview and `project-requirement.md` for the original project scope.

## Requirements

- PHP 8.5
- Composer
- Node.js / NPM
- Docker (for the Postgres database — see below)

## Database

Postgres runs in a container via Laravel Sail's Docker Compose config (`compose.yaml`). The app itself runs natively (`php artisan serve` / `npm run dev`), only the database is containerized.

```bash
docker compose up -d pgsql
```

This starts Postgres on `127.0.0.1:5432` with the credentials already set in `.env.example` (`invoicely` / `sail` / `password`). Sail's init script also creates a `testing` database automatically, used by the test suite.

To stop the container: `docker compose down` (add `-v` to also delete the data volume).

## Setup

```bash
git clone <repo-url> invoicely
cd invoicely

composer install
npm install

cp .env.example .env
php artisan key:generate

docker compose up -d pgsql
php artisan migrate

npm run build
```

## Development

```bash
composer run dev
```

This runs the PHP server, queue listener, log watcher, and Vite dev server together. Alternatively, run them individually:

```bash
php artisan serve
npm run dev
```

### Admin panel (staff)

Create an admin user, then log in at `/admin`:

```bash
php artisan make:filament-user
```

Manage clients, invoices (with a line-item repeater and a live-computed total), and view the read-only payments ledger. The dashboard shows total revenue, outstanding invoices, and recent payments.

### Client portal

Clients don't self-register. Staff create the client record in `/admin`, then the client sets their own password via **Forgot password** at `/client/login` (an emailed reset link — with `MAIL_MAILER=log`, check `storage/logs/laravel.log` for it locally). Once logged in, a client sees only their own invoices at `/client` and can pay an unpaid invoice via the "Pay Now" button.

### API

Clients can also access their invoices programmatically via Sanctum tokens:

```bash
php artisan tinker --execute 'echo App\Models\Client::first()->createToken("mobile-app")->plainTextToken;'
```

```bash
curl -H "Authorization: Bearer <token>" -H "Accept: application/json" http://localhost:8000/api/invoices
curl -H "Authorization: Bearer <token>" -H "Accept: application/json" http://localhost:8000/api/invoices/{id}
curl -X POST -H "Authorization: Bearer <token>" -H "Accept: application/json" http://localhost:8000/api/invoices/{id}/checkout
```

A client can only see and check out their own invoices — cross-client access returns 403.

## Testing

The test suite runs against the same Postgres container (the `testing` database), so it must be running first:

```bash
docker compose up -d pgsql
php artisan test --compact
```

Run a specific test file or filter:

```bash
php artisan test --compact --filter=SomeTestName
```

## Code style & static analysis

```bash
vendor/bin/pint          # fix formatting
vendor/bin/pint --test   # check formatting without fixing
vendor/bin/phpstan analyse # static analysis (larastan)
```

## Stripe

Add your Stripe keys to `.env`:

```
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

Without real keys, checkout/webhook code paths aren't reachable manually (the test suite mocks Stripe entirely, so `php artisan test` doesn't need real keys). To try it against real Stripe test-mode keys locally, forward webhooks with the Stripe CLI:

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

Copy the webhook signing secret it prints into `STRIPE_WEBHOOK_SECRET`.

## Deployment

This is a runbook, not automation — nothing here has been executed against a real environment. [Laravel Cloud](https://cloud.laravel.com/) is the fastest path for a Laravel app like this if you don't already have infrastructure; the steps below apply regardless of host.

1. **Database.** The local `compose.yaml`/Sail Postgres container is dev-only. Production needs a real managed/hosted Postgres instance (Laravel Cloud, RDS, a DigitalOcean/Supabase managed database, etc.) — point `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` at it. Check whether your provider requires SSL for remote connections (`DB_SSLMODE` / `sslmode=require` in the connection options).

2. **Environment.** Copy `.env.example` to `.env` on the server, generate a fresh `APP_KEY` (`php artisan key:generate`), set `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL` to your real HTTPS domain.

3. **Stripe live keys.** Test-mode and live-mode are entirely separate in Stripe. Swap `STRIPE_KEY`/`STRIPE_SECRET` for live-mode values, then in the Stripe Dashboard register a **live** webhook endpoint pointing at `https://yourdomain.com/stripe/webhook` (event: `checkout.session.completed`) and copy its signing secret into `STRIPE_WEBHOOK_SECRET`. This is a separate registration from any test-mode/CLI webhook you used locally.

4. **HTTPS.** Required by Stripe for live webhooks. Make sure TLS actually terminates somewhere in front of the app (the hosting platform, a load balancer, or a reverse proxy like Nginx/Caddy) — Laravel itself doesn't serve TLS.

5. **Migrations.** `php artisan migrate --force` (the `--force` flag is required in production since the interactive confirmation prompt is skipped there).

6. **Queue worker.** The Stripe webhook handler itself runs synchronously (no queued jobs), but `QUEUE_CONNECTION=database` is still used elsewhere (e.g. Fortify's notification emails), so a persistent worker process is still needed: `php artisan queue:work` under Supervisor/systemd, or your platform's managed worker feature.

7. **Build & optimize.**
   ```bash
   composer install --optimize-autoloader --no-dev
   npm run build
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   php artisan filament:optimize
   ```
   Re-run the cache commands after every deploy that changes config, routes, or views — stale caches are a common source of "it works locally but not in prod" bugs.

8. **First admin user.** `php artisan make:filament-user` on the production server (or a one-off Artisan command in your deploy pipeline). Production's password rules are already stricter than local (see `AppServiceProvider::configureDefaults()` — 12+ characters, mixed case, symbols, and checked against known breaches when `app()->isProduction()`).

9. **Backups.** Set up automated backups for the production Postgres database — most managed providers offer point-in-time recovery or scheduled snapshots out of the box; if self-hosting, schedule `pg_dump` and store it off-server. Periodically test that a backup actually restores.
