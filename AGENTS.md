# Agent instructions for this repository

This is a Laravel 13 + Vue 3 (Inertia.js) application, run through
**Laravel Sail** (Docker), not a bare PHP/Composer/Node install. Do not try
to install PHP, Composer, or run `php`/`composer` directly on the host —
there may be none on this machine by design.

## Running commands

Always go through Sail once the containers exist:

```sh
./vendor/bin/sail artisan ...
./vendor/bin/sail composer ...
./vendor/bin/sail npm ...
./vendor/bin/sail test
```

If `vendor/` doesn't exist yet (fresh clone), bootstrap Composer once via
Docker directly, then switch to `sail` for everything else:

```sh
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
./vendor/bin/sail up -d
```

Check `./vendor/bin/sail ps` to see if containers are already running before
starting them again.

## Project layout

- `routes/web.php` — all routes (this app has no separate API route file;
  Inertia serves pages, not JSON)
- `app/Http/Controllers/`, `app/Models/`, `app/Policies/`,
  `app/Http/Requests/` — standard Laravel MVC+ layout
- `resources/js/Pages/` — one Vue component per Inertia route, resolved by
  name from the controller (`Inertia::render('Tasks/Index', [...])`)
- `resources/js/stores/` — Pinia stores
- `database/migrations/`, `database/factories/`, `database/seeders/`
- `LEARNING.md` — if a change touches a concept covered there, keep it in
  sync (it's intentionally kept accurate, not just illustrative)

## Conventions to preserve

- SQLite for local dev (`database/database.sqlite`) — don't switch the
  default DB connection without discussing it, that's a deliberate
  zero-config choice for this scaffold
- Form Requests for validation, Policies for authorization (see
  `app/Http/Requests/`, `app/Policies/`) — don't inline ad-hoc `$request->validate()`
  or manual `can()` checks scattered in controllers when a FormRequest/Policy
  is the established pattern here
- Comments in this codebase are sparse by design, limited to Symfony/React
  parallels worth calling out (see `LEARNING.md`) — don't add routine
  "what this does" comments
