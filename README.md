# Route Notes

Add human‑readable notes to your Laravel routes and surface them directly in `php artisan route:list`.

[![Tests](https://github.com/Jarryd-W-Hoffman/route-notes/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Jarryd-W-Hoffman/route-notes/actions/workflows/tests.yml)

## 🤔 Why This Exists

This package was born out of necessity while collaborating on a 10-year-old codebase that migrated through several version of Laravel.

When you're staring at 5,000+ routes, many of which are dead, deprecated, or "temporarily" added for a legacy sync, documentation is often non-existant, buried in Slack threads or ancient Jira tickets.

Laravel documents what a route **`does`**; this package documents **`why`**. It gives your team a way to leave "sticky notes" directly on the route list so you can identify sensitive endpoints, legacy baggage, or internal-only tools at a glance.

## ✨ Features

* ✅ **Fluent API** — Attach notes directly to route definitions.
* ✅ **Controller DocBlocks** — Extract `@note` tags from controller methods automatically.
* ✅ **Filtering** — Show only documented routes with the `--notes` flag.
* ✅ **Legacy Friendly** — Supports everything from Laravel 10 through 12.
* ✅ **Zero Config** — Install and run. No service providers to register or config to publish.
* ✅ **Cache Safe** — Fully compatible with `route:cache`.

## 📦 Installation

```bash
composer require jarryd/laravel-route-notes
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

**2.PHPDoc Support**

Keep your documentation close to the code. Define notes directly in your controller’s PHPDoc.

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

**3.Combining Both**

Fluent notes and PHPDoc notes are merged automatically. Fluent notes always appear first.

```php
Route::get('/users', [UserController::class, 'index'])
    ->note('High Priority');

// Result: "High Priority | Used by Admin Portal only | ..."
```

**4.The Result**

Run the standard Laravel command:

```bash
php artisan route:list
```

| Method | URI               | Name            | Action                  | Note                      |
|--------|-------------------|-----------------|-------------------------|---------------------------|
| GET    | api/legacy-sync   | sync.handle     | SyncController@handle   | Legacy sync for old ERP   |
| POST   | webhooks/stripe   |                 | StripeController        | Production only           |
| GET    | admin/reports     | admin.reports   | ReportController        | Admin Portal only         |

## 🔎 Filtering
When auditing a massive route file, you can hide the noise and only show routes that have context attached:

```bash
php artisan route:list --notes
```

## 🎯 Compatibility & Support

| Feature               | Status | Notes                                         |
|-----------------------|--------|-----------------------------------------------|
| Controller Actions    | ✅     | Supports Controller@method                    |
| Invokable Controllers | ✅     | Supports __invoke                             |
| Route Caching         | ✅     | Fully compatible with route:cache             |
| Laravel Versions      | ✅     | Supports 10.x, 11.x, and 12.x                  |
| Closures              | ⚠️     | Fluent ->note() works; PHPDoc does not.        |
| Class-level tags      | ❌     | Method-level @note only.                      |

## 📄 License
The MIT License (MIT). Please see [License File](./LICENSE) for more information.
