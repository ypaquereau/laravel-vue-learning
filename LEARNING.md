# Laravel + Vue, for a Symfony + React developer

This repo is a learning scaffold, not a tutorial app you copy-paste from. It's
a working Laravel 13 + Inertia + Vue 3 app with one real feature (a Tasks
CRUD) built so that every Laravel/Vue concept below has actual code behind it
in this repo. Read a section, then open the file(s) it points at.

The mapping is deliberately *approximate* — Laravel and Symfony solve the same
problems with different philosophies (convention-over-configuration vs
explicit configuration), so "X is Y" below means "X plays Y's role," not "X
behaves identically to Y."

## Running it

```bash
./vendor/bin/sail up -d          # php + mailpit containers
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm run dev    # Vite dev server with HMR
```

Visit `http://localhost:8000`, register or log in as the seeded user
(`test@example.com` / `password`), then click **Tasks** in the nav.

---

## Part 1 — Laravel vs Symfony

### Routing

Symfony routes live next to the controller as `#[Route]` attributes (or in
YAML). Laravel routes live in dedicated files under `routes/` —
[`routes/web.php`](routes/web.php) for this app — and are matched top-to-bottom
against the request.

```php
// routes/web.php
Route::resource('tasks', TaskController::class)->only([
    'index', 'create', 'store', 'edit', 'update', 'destroy',
]);
```

`Route::resource` is the one-line equivalent of writing a `#[Route]` attribute
over each of `index`/`create`/`store`/`edit`/`update`/`destroy` — it derives
all 6 URIs, HTTP verbs, and route *names* (`tasks.index`, `tasks.edit`, …) from
the controller name and the resource name. Run
`./vendor/bin/sail artisan route:list` to see what it expanded to — the
closest Laravel equivalent of `bin/console debug:router`.

Route **names**, not URIs, are what you reference in code and in Blade/Inertia
(`route('tasks.edit', $task)`), exactly like Symfony's route `name:` plus the
`path()` Twig function — except Laravel exposes the same helper to JavaScript
via [Ziggy](https://github.com/tighten/ziggy) (already wired into
[`resources/js/app.js`](resources/js/app.js)), so Vue components call
`route('tasks.edit', task.id)` too. There's no separate "API route" file for
this app — Inertia routes serve HTML-ish responses, not JSON, so they live in
`web.php` the same as a page you'd render with Twig.

### The DI container

Symfony's container is configured explicitly (services.yaml, autowiring by
type-hint). Laravel's container (`Illuminate\Container\Container`) also
autowires by type-hint, but almost nothing needs explicit registration: a
class with no interface dependencies just resolves. You've already used this
without noticing — every controller method that type-hints a `Request` or a
`FormRequest` subclass (see [`TaskController`](app/Http/Controllers/TaskController.php))
is the container injecting it, the same mechanism as a Symfony controller
argument resolver.

Where you'd write a Symfony service and bind it to an interface in
`services.yaml`, Laravel does it in a **service provider** — see
[`app/Providers/AppServiceProvider.php`](app/Providers/AppServiceProvider.php),
specifically its `boot()` method, the rough equivalent of a Symfony
compiler pass or `services.yaml` `bind:` block. For a scaffold this small
there's nothing to bind yet, which is itself the point: unlike Symfony,
*not* configuring anything is the default, working state.

### Eloquent vs Doctrine

This is the biggest mental shift. Doctrine is a **Data Mapper**: your entity
is a plain PHP object that knows nothing about the database; the
`EntityManager` tracks it and writes changes on `flush()`. Eloquent is
**Active Record**: the model *is* the query builder and the row.

```php
// Symfony/Doctrine
$task = new Task();
$task->setTitle('Write LEARNING.md');
$entityManager->persist($task);
$entityManager->flush();

// Laravel/Eloquent — see app/Http/Controllers/TaskController.php@store
$request->user()->tasks()->create($request->validated());
```

No `persist()`, no `flush()`, no unit-of-work. `$task->save()` writes
immediately. There's also no repository class by default — `Task::where(...)`,
`$user->tasks()`, etc. *are* the repository, built into the model. If you want
a Doctrine-style repository layer for testability, you write one yourself (a
`TaskRepository` class wrapping `Task::query()`); Laravel doesn't impose it.

Relationships are methods, not mapped properties:
[`app/Models/Task.php`](app/Models/Task.php)'s `user()` (`belongsTo`, Doctrine's
`#[ManyToOne]`) and [`app/Models/User.php`](app/Models/User.php)'s `tasks()`
(`hasMany`, Doctrine's `#[OneToMany(mappedBy: ...)]`). Calling `$task->user`
(no parens, magic property access) lazy-loads it — same N+1 risk as Doctrine's
lazy proxies, same fix (`->with('user')` instead of
`JOIN`/`fetch: EAGER`).

Mass-assignment protection is the other surprise: Eloquent models default to
**blocking** `fill()`/`create()` for every column, until you allow-list them —
see the `#[Fillable(...)]` attribute on `Task` and `User`. There's no direct
Doctrine analogue; the closest comparable risk Doctrine guards against
differently is binding a Symfony Form directly to an entity without a DTO.

Migrations are plain PHP describing the schema directly (`Schema::create` in
[`database/migrations/..._create_tasks_table.php`](database/migrations)),
run with `artisan migrate`. Doctrine migrations are usually *generated* as a
diff against your mapped entities; Laravel has no mapping step to diff
against, so you write the migration by hand and Eloquent just reads whatever
columns exist at runtime.

### Artisan vs Symfony Console

Both are a CLI built on the same idea (a kernel that resolves and runs
command classes), and both read suspiciously similarly:
`php bin/console make:controller` vs `php artisan make:controller`. The
commands that scaffolded this feature:

```bash
php artisan make:model Task -a --requests   # model, migration, factory, seeder, policy,
                                             # resource controller, 2 form requests — one command
```

Symfony's MakerBundle generates similar pieces but one at a time, with less
built-in convention about how they connect; Artisan's generators lean harder
on naming conventions (a `Task` model implies `App\Policies\TaskPolicy`,
`TaskFactory`, `tasks` table) so one command wires up more.

### Middleware

Conceptually identical to Symfony's event-based kernel listeners /
`RequestMatcher`-gated firewalls — code that runs before/after a request, able
to short-circuit it. The difference is purely mechanical: Laravel middleware
is an explicit, ordered pipeline you attach per-route rather than global
listeners matched by priority and request pattern.

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::resource('tasks', TaskController::class)->only([...]);
});
```

`auth` here is the same job as a Symfony `access_control` rule gating a URL
pattern on `IS_AUTHENTICATED_FULLY` — but it's declared at the route, not in a
separate `security.yaml`. [`HandleInertiaRequests`](app/Http/Middleware/HandleInertiaRequests.php)
is a custom middleware in the request pipeline for every Inertia response —
see "Inertia" below for what it's actually doing.

### Service providers vs bundles/services

A Symfony bundle bundles routes + config + services + a compiler pass. A
Laravel package does the same job via a service provider's `register()`
(bind things into the container) and `boot()` (do things once everything is
registered — publish config, register view paths, etc). Every Composer
package you installed here (`laravel/breeze`, `laravel/sail`,
`inertiajs/inertia-laravel`) registered itself this way; `AppServiceProvider`
is where *your own* app-level bindings go, the thin slice of `services.yaml`
that a small app actually needs.

### Blade vs Twig — and why this app barely uses either

Blade (`{{ $var }}`, `@if`, `@foreach`) is Laravel's Twig: server-rendered PHP
templates, compiled to plain PHP and cached, same job as Twig compiling to
PHP. This app uses Blade for exactly one file —
[`resources/views/app.blade.php`](resources/views/app.blade.php) — the shell
HTML page that Vite's assets get injected into. Every *actual* page
(`Tasks/Index`, `Dashboard`, …) is a Vue component, not a Blade view, because
this app uses **Inertia** instead of server-rendered templates for anything
dynamic. If you were building a classic server-rendered Laravel app instead,
Blade is where you'd reach for `@extends`/`@section`/`@include`, the direct
analogues of Twig's `{% extends %}`/`{% block %}`/`{% include %}`.

### Form Requests vs Symfony Forms + Validator

A Symfony Form type declares fields and constraints together; you
`handleRequest()` it and check `isValid()`. A Laravel **FormRequest**
([`app/Http/Requests/StoreTaskRequest.php`](app/Http/Requests/StoreTaskRequest.php),
[`UpdateTaskRequest.php`](app/Http/Requests/UpdateTaskRequest.php)) is the
same bundling of "who may submit this" (`authorize()`) and "what shape is
valid" (`rules()`), resolved by the container and validated *before* your
controller method body runs — fail validation and the controller method
never executes; Inertia turns the resulting 422 into `form.errors` on the
Vue side automatically (see `useForm` in
[`resources/js/Pages/Tasks/Create.vue`](resources/js/Pages/Tasks/Create.vue)).
There's no separate "build the form, render the form, bind the form" step;
the HTML form is just a plain Vue template (see Part 2).

### Policies vs Voters

A Laravel **Policy** is a Voter scoped to one model with one method per
action, auto-resolved from the model's class name — see
[`app/Policies/TaskPolicy.php`](app/Policies/TaskPolicy.php).
`$user->can('update', $task)` and Symfony's
`$this->denyAccessUnlessGranted('EDIT', $task)` do the same job. The wiring
difference: Symfony Voters are tagged services you register and that get
asked in turn until one votes; Laravel auto-discovers `App\Policies\{Model}Policy`
by naming convention, so nothing is registered explicitly unless you break
the naming convention (then it's one line in a service provider).

Two call sites, both authorizing against the same policy:
`UpdateTaskRequest::authorize()` (declarative, runs before the controller) and
`$this->authorize('delete', $task)` inside `TaskController::destroy()`
(imperative, because `destroy` has no FormRequest of its own).

---

## Part 2 — Vue vs React

### Components

A `.vue` Single-File Component bundles `<script setup>` + `<template>` +
`<style>` in one file — compare a `.jsx`/`.tsx` file that bundles JS and
markup but usually imports CSS separately. The template isn't JSX: it's an
HTML superset with its own directives (`v-if`, `v-for`, `v-model`) compiled
ahead-of-time by Vite, rather than JSX's "it's just function calls"
philosophy. Browse [`resources/js/Components/`](resources/js/Components/) —
`TextInput.vue`, `Checkbox.vue`, `PrimaryButton.vue` — these are what
`<Button>`/`<Input>` would be in a React design-system folder.

### Composition API vs hooks

`<script setup>` + `ref`/`computed`/`watch` is Vue's answer to React's
`useState`/`useMemo`/`useEffect`. The biggest difference: a `ref` is a boxed
value (`count.value++`), not a `[value, setValue]` pair, and there's no
dependency array to get wrong — `computed`/`watch` figure out their own
dependencies by tracking which refs you actually read, no `useMemo(() => …,
[a, b])` footgun. Compare
[`resources/js/Components/FlashToast.vue`](resources/js/Components/FlashToast.vue)
(`watch(() => page.props.flash.success, …)`, the Vue version of
`useEffect(() => {...}, [props.flash.success])`) against a `useState` +
`useEffect` toast in React. Also note: nothing in `<script setup>` is
`return`ed — every top-level `const`/`function` is automatically exposed to
the template, unlike a React component's explicit JSX return.

### props / emits vs props / callbacks

React passes data down via props and bubbles events up via callback props
(`onChange`) you invent yourself. Vue passes data down via `defineProps` (same
idea) and bubbles events up via `defineEmits` — a *declared* event name Vue
itself dispatches, not just another prop:

```vue
<!-- resources/js/Components/Checkbox.vue -->
<script setup>
const emit = defineEmits(['update:checked']);
...
set(val) { emit('update:checked', val); }
</script>
```

`v-model:checked="form.completed"` on the consuming side (see
[`Tasks/Edit.vue`](resources/js/Pages/Tasks/Edit.vue)) is syntactic sugar
for `:checked="form.completed" @update:checked="form.completed = $event"` —
the Vue compiler generates that callback wiring for you. A controlled React
checkbox needs the same `checked` + `onChange` pair, just written out by hand
every time.

### Vue Router vs React Router — the one that's *missing*

This app has **no Vue Router**, deliberately. A typical Vue SPA talking to a
JSON API would use Vue Router for client-side routes, the same role as React
Router. This app uses **Inertia** instead (see below), which means Laravel's
own router (Part 1) is the only router — there's no client-side route table
to keep in sync with the server's. If you build a second Laravel+Vue project
as a pure SPA behind a token-authenticated API (Sanctum), *that's* when
you'd reach for Vue Router, mirroring a React SPA's React Router setup.

### Pinia vs Redux / Context

Pinia is Vue's current state-management library — think a single Redux store
(or a Context + `useReducer` pair) without the boilerplate: no action types,
no reducers, no `<Provider>` wrapping. One call,
[`resources/js/stores/flash.js`](resources/js/stores/flash.js), defines
state + the functions that mutate it; any component calls `useFlashStore()`
and gets the *same* live instance — registered once in
[`resources/js/app.js`](resources/js/app.js) (`.use(pinia)`), the Vue
equivalent of wrapping your React tree in `<Provider store={store}>`.

This app's one store is intentionally small: the toast message
`TaskController` flashes into the session on redirect
(`->with('success', 'Task created.')`) gets picked up by `HandleInertiaRequests`
([`app/Http/Middleware/HandleInertiaRequests.php`](app/Http/Middleware/HandleInertiaRequests.php))
as a shared prop, then by `FlashToast.vue`'s `watch()`, which pushes it into
Pinia so it can animate out on a timer instead of vanishing the instant
Inertia swaps the page. For most data in this app, you *don't* need Pinia —
page props from the server (`tasks`, `task`) are already reactive local state,
the same way you wouldn't put server-fetched data no other component needs
into Redux.

### Inertia.js — the concept with no direct equivalent on either side

Inertia is neither "server-rendered Blade" nor "React/Vue SPA behind a JSON
API" — it's a third thing. The server controller returns
`Inertia::render('Tasks/Index', ['tasks' => $tasks])`
([`TaskController@index`](app/Http/Controllers/TaskController.php)): a
component name plus props, serialized as JSON on an XHR
("visit"), or embedded in the initial HTML on a hard load. The Vue side
(`app.js`) resolves that component name to a real `.vue` file and mounts it
with those props — no `fetch()`, no loading spinner, no separate REST/GraphQL
layer to design and keep in sync with the backend. You get the Laravel-side
developer experience (routes, controllers, FormRequests, policies, all in
PHP) with the Vue-side rendering experience (real components, not
template strings), without writing or maintaining an API.

The closest single-sentence pitch: it's what you'd get if Symfony's
controller could `return $this->render('react-component.jsx', [...])`
directly — which neither React nor Symfony actually offers out of the box.

---

## Part 3 — Where the Tasks feature lives

| Concept | File |
|---|---|
| Routes | [`routes/web.php`](routes/web.php) |
| Migration | [`database/migrations/..._create_tasks_table.php`](database/migrations) |
| Model + relationships | [`app/Models/Task.php`](app/Models/Task.php), [`app/Models/User.php`](app/Models/User.php) |
| Validation | [`app/Http/Requests/StoreTaskRequest.php`](app/Http/Requests/StoreTaskRequest.php), [`UpdateTaskRequest.php`](app/Http/Requests/UpdateTaskRequest.php) |
| Authorization | [`app/Policies/TaskPolicy.php`](app/Policies/TaskPolicy.php) |
| Controller | [`app/Http/Controllers/TaskController.php`](app/Http/Controllers/TaskController.php) |
| Pages (Vue) | [`resources/js/Pages/Tasks/`](resources/js/Pages/Tasks/) |
| Shared state (Pinia) | [`resources/js/stores/flash.js`](resources/js/stores/flash.js) |
| Fixtures | [`database/factories/TaskFactory.php`](database/factories/TaskFactory.php), [`database/seeders/DatabaseSeeder.php`](database/seeders/DatabaseSeeder.php) |

## Part 4 — Exercises to push this further

Roughly in order of how much new ground each one covers:

1. **Add a `due_date` column** — migration, form field, and sort `Index` by
   it. Low-risk way to practice the migration → model → request → Vue chain
   end to end.
2. **Add a feature test** — `php artisan make:test TaskControllerTest`, then
   assert a guest is redirected from `/tasks` and a second user can't edit
   someone else's task (exercises the Policy). Laravel's `RefreshDatabase`
   trait is the `DAMA\DoctrineTestBundle` of this stack.
3. **Add task categories** — a `Category` model, `belongsTo`/`hasMany`
   between `Task` and `Category`, and a `<select>` in `Create.vue`/`Edit.vue`.
   Practices a second real Eloquent relationship beyond the User↔Task one.
4. **Swap Pinia's role** — add a client-only "hide completed tasks" toggle to
   the flash store (or a new store) and use it in `Tasks/Index.vue`, to feel
   the difference between state that round-trips the server (`tasks` prop)
   and state that's purely client-side (a Pinia store), the same distinction
   as "server state" vs "client state" in a React app.
