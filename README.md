# Route Notes

Add human‑readable notes to your Laravel routes and surface them directly in `php artisan route:list`.

[![Tests](https://github.com/Jarryd-W-Hoffman/route-notes/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Jarryd-W-Hoffman/route-notes/actions/workflows/tests.yml)

## 🤔 Why This Exists

This package was born out of necessity while collaborating on a 10-year-old codebase that migrated through several versions of Laravel.

When you're staring at 5,000+ routes, many of which are dead, deprecated, or "temporarily" added for a legacy sync, documentation is often non-existent, buried in Slack threads or ancient Jira tickets.

Laravel documents what a route **`does`**; this package documents **`why`**. It gives your team a way to leave "sticky notes" directly on the route list so you can identify sensitive endpoints, legacy baggage, or internal-only tools at a glance.

## ✨ Features

* ✅ **Fluent API** — Attach notes directly to route definitions.
* ✅ **Controller DocBlocks** — Extract `@note` tags from controller methods automatically.
* ✅ **Filtering** — Show only documented routes with the `--notes` flag.
* ✅ **Native Output** — Keeps Laravel's `route:list` layout, `-v` middleware and `--json` output intact.
* ✅ **Runtime Safe** — Notes are metadata only; they never reach your controllers or route parameters.
* ✅ **Zero Config** — Install and run. No service providers to register or config to publish.
* ✅ **Cache Safe** — Fully compatible with `route:cache`.

## 📦 Installation

```bash
composer require jarryd/route-notes
```

_Zero config. No service providers to register. No assets to publish._

## 🚀 Usage

**1. Fluent Interface**

Attach notes to any route. Chaining `note()` multiple times or using `notes()` with an array will append them.

```php
Route::get('/api/legacy-sync', [SyncController::class, 'handle'])
    ->name('sync.handle')
    ->note('Legacy sync for old ERP')
    ->note('Scheduled for removal in Q4');

// Or via array
Route::post('/webhooks/stripe', StripeController::class)
    ->notes([
        'Production only',
        'Requires secret validation'
    ]);
```

**2. PHPDoc Support**

Keep your documentation close to the code. Define notes directly in your controller’s PHPDoc. Each `@note` must start its own line.

```php
class UserController extends Controller
{
    /**
     * @note Used by Admin Portal only
     * @note Rate limited to 5 req/min
     */
    public function index() { ... }

    // ...
}

// Invokable controllers are also supported
class ReportController
{
    /** @note Writes to ledger */
    public function __invoke() { ... }
}
```

**3. Combining Both**

Fluent notes and PHPDoc notes are merged automatically. Fluent notes always appear first, and duplicates are removed.

```php
Route::get('/users', [UserController::class, 'index'])
    ->note('High Priority');

// Notes: High Priority, Used by Admin Portal only, Rate limited to 5 req/min
```

**4. The Result**

Run the standard Laravel command:

```bash
php artisan route:list
```

Notes appear beneath each route, alongside Laravel's usual output:

```
  GET|HEAD   api/legacy-sync ............................ sync.handle › SyncController@handle
             ✎ Legacy sync for old ERP
             ✎ Scheduled for removal in Q4
  GET|HEAD   users ......................................................... UserController@index
             ✎ High Priority
             ✎ Used by Admin Portal only
             ✎ Rate limited to 5 req/min
  POST       webhooks/stripe ................................................... StripeController
             ✎ Production only
             ✎ Requires secret validation

                                                                          Showing [3] routes
```

With `--json`, each route gains a `note` array:

```json
{"method": "POST", "uri": "webhooks/stripe", "action": "App\\Http\\Controllers\\StripeController", "note": ["Production only", "Requires secret validation"], "...": "..."}
```

## 🔎 Filtering
When auditing a massive route file, you can hide the noise and only show routes that have context attached:

```bash
php artisan route:list --notes
```

It combines with the built-in filters:

```bash
php artisan route:list --notes --path=api --json
```

## 🎯 Compatibility & Support

| Feature               | Status | Notes                                         |
|-----------------------|--------|-----------------------------------------------|
| Controller Actions    | ✅     | Supports Controller@method                    |
| Invokable Controllers | ✅     | Supports __invoke                             |
| Route Caching         | ✅     | Fully compatible with route:cache             |
| Laravel Versions      | ✅     | Supports 12.x and 13.x                        |
| `--json` Output       | ✅     | Notes included as a `note` array              |
| Closures              | ⚠️     | Fluent ->note() works; PHPDoc does not.       |
| Class-level tags      | ❌     | Method-level @note only.                      |

## 📄 License
The MIT License (MIT). Please see [License File](./LICENSE) for more information.
