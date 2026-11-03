# Laravel Vue Learning

A Laravel 13 + Vue 3 (via Inertia.js) learning scaffold, built as a
Symfony/React developer's on-ramp to Laravel and Vue. It includes a working
**Tasks** CRUD feature exercising routing, Eloquent, Form Requests, Policies,
Vue's Composition API, and Pinia — all mapped back to their Symfony/React
equivalents.

**Start here: [LEARNING.md](LEARNING.md)** — concept-by-concept mapping with
links into this repo's actual code.

## Requirements

Just [Docker](https://www.docker.com/). No PHP, Composer, or Node install
required on the host — everything runs through
[Laravel Sail](https://laravel.com/docs/sail), Laravel's own Docker dev
environment.

## Getting started

```bash
git clone git@github.com:ypaquereau/laravel-vue-learning.git
cd laravel-vue-learning
cp .env.example .env

docker run --rm -v "$PWD":/app -w /app composer:2 composer install

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Visit [http://localhost:8000](http://localhost:8000). Log in with the seeded
user: `test@example.com` / `password`.

Useful commands once Sail is up:

```bash
./vendor/bin/sail artisan ...   # any artisan command, inside the container
./vendor/bin/sail npm ...       # any npm command, inside the container
./vendor/bin/sail test          # run the test suite
./vendor/bin/sail down          # stop the containers
```

Mailpit (catches outgoing mail locally, e.g. password resets) is available
at [http://localhost:8025](http://localhost:8025).

## Stack

- **Backend:** Laravel 13, SQLite (zero-config, file-based — see `database/database.sqlite`)
- **Frontend:** Vue 3 (Composition API, `<script setup>`), Pinia
- **Bridge:** [Inertia.js](https://inertiajs.com) — server-side routing/controllers
  render Vue page components directly, no separate JSON API layer
- **Auth scaffolding:** [Laravel Breeze](https://laravel.com/docs/starter-kits) (Vue stack)
- **Dev environment:** [Laravel Sail](https://laravel.com/docs/sail) (Docker)
