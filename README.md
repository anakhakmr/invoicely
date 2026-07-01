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

For local webhook testing:

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```
