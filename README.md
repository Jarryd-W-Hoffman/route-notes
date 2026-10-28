# Route Notes

Add human‑readable notes to your Laravel routes and surface them directly in `php artisan route:list`.

[![Tests](https://github.com/Jarryd-W-Hoffman/route-notes/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Jarryd-W-Hoffman/route-notes/actions/workflows/tests.yml)

## 🤔 Why This Exists

This package was born out of necessity while collaborating on a 10-year-old codebase that migrated through several versions of Laravel.

When you're staring at 5,000+ routes, many of which are dead, deprecated, or "temporarily" added for a legacy sync, documentation is often non-existent, buried in Slack threads or ancient Jira tickets.

Laravel documents what a route **`does`**; this package documents **`why`**. It gives your team a way to leave "sticky notes" directly on the route list so you can identify sensitive endpoints, legacy baggage, or internal-only tools at a glance.

## ✨ Features

* ✅ **Fluent API** — Attach notes directly to route definitions.
* ✅ **Controller DocBlocks & Attributes** — Read `@note` tags and `#[Note]` attributes from controller classes and methods.
* ✅ **Levels** — Flag notes as `warning`, `deprecated` or `danger`, color-coded in the terminal.
* ✅ **Filtering** — Show only documented routes with `--notes`, or search them with `--note=legacy`.
* ✅ **Markdown Export** — Generate a notes report for wikis and PRs with `php artisan route:notes`.
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
    ->note('Scheduled for removal in Q4', 'deprecated');

// Or via array
Route::post('/webhooks/stripe', StripeController::class)
    ->notes([
        'Production only',
        'Requires secret validation'
    ]);
```

**2. Levels**

Every note has a level: `info` (the default), `warning`, `deprecated` or `danger`. Pass a level name or the `NoteLevel` enum:

```php
use Jarryd\RouteNotes\NoteLevel;

Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
    ->note('Hard deletes customer data', NoteLevel::Danger)
    ->note('Use /v2/customers instead', 'deprecated');

Route::post('/sync', SyncController::class)
    ->notes(['Slow', 'Not idempotent'], 'warning');
```

An unknown level name throws an `InvalidArgumentException`, so typos don't go unnoticed.

**3. PHPDoc Support**

Keep your documentation close to the code. Define notes directly in your controller’s PHPDoc. Each `@note` must start its own line, and continues onto following lines until a blank line or another tag. Prefix the text with `[level]` to set a level.

```php
class UserController extends Controller
{
    /**
     * @note Used by Admin Portal only
     * @note Rate limited to 5 req/min
     */
    public function index() { ... }

    /**
     * @note [deprecated] Replaced by the bulk export endpoint, which
     *       streams results instead of building them in memory.
     */
    public function export() { ... }
}

// Invokable controllers are also supported
class ReportController
{
    /** @note Writes to ledger */
    public function __invoke() { ... }
}
```

Notes in a controller's class docblock apply to every route using that controller:

```php
/**
 * @note Owned by the integrations team
 */
class ErpController extends Controller { ... }
```

**4. Attributes**

Prefer attributes? `#[Note]` works on controller classes and methods, and can be repeated:

```php
use Jarryd\RouteNotes\Note;
use Jarryd\RouteNotes\NoteLevel;

#[Note('Owned by the integrations team')]
class LedgerController extends Controller
{
    #[Note('Writes to ledger')]
    #[Note('Remove after the Q4 migration', level: NoteLevel::Deprecated)]
    public function store() { ... }
}
```

**5. Combining Them**

All sources are merged automatically, in this order: fluent notes, then class notes, then method notes (PHPDoc before attributes). Duplicates are removed.

```php
Route::get('/users', [UserController::class, 'index'])
    ->note('High Priority');

// Notes: High Priority, Used by Admin Portal only, Rate limited to 5 req/min
```

**6. The Result**

Run the standard Laravel command:

```bash
php artisan route:list
```

Notes appear beneath each route, alongside Laravel's usual output, colored by level:

```
  GET|HEAD   api/legacy-sync ............................ sync.handle › SyncController@handle
             ✎ Legacy sync for old ERP
             ✎ [deprecated] Scheduled for removal in Q4
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
{
    "method": "GET|HEAD",
    "uri": "api/legacy-sync",
    "note": [
        {"text": "Legacy sync for old ERP", "level": "info"},
        {"text": "Scheduled for removal in Q4", "level": "deprecated"}
    ]
}
```

## 🔎 Filtering
When auditing a massive route file, you can hide the noise and only show routes that have context attached:

```bash
php artisan route:list --notes
```

To search notes, pass `--note`. It matches note text (case-insensitive) or a level name:

```bash
php artisan route:list --note=legacy
php artisan route:list --note=deprecated
```

Both combine with the built-in filters:

```bash
php artisan route:list --notes --path=api --json
```

## 📝 Markdown Reports

`route:notes` exports every route with notes as a Markdown table, ready for a wiki page or PR description:

```bash
php artisan route:notes
php artisan route:notes --note=deprecated --output=docs/deprecated-routes.md
```

```markdown
| Method | URI | Name | Action | Notes |
| --- | --- | --- | --- | --- |
| GET\|HEAD | `api/legacy-sync` | `sync.handle` | `App\Http\Controllers\SyncController@handle` | Legacy sync for old ERP<br>**Deprecated:** Scheduled for removal in Q4 |
```

It accepts the same filters as `route:list`, as well as `--json`.

## 🎯 Compatibility & Support

| Feature               | Status | Notes                                         |
|-----------------------|--------|-----------------------------------------------|
| Controller Actions    | ✅     | Supports Controller@method                    |
| Invokable Controllers | ✅     | Supports __invoke                             |
| Route Caching         | ✅     | Fully compatible with route:cache             |
| Laravel Versions      | ✅     | Supports 12.x and 13.x                        |
| `--json` Output       | ✅     | Notes included as a `note` array              |
| PHP Attributes        | ✅     | `#[Note]` on classes and methods              |
| Class-level tags      | ✅     | Apply to every action on the controller       |
| Closures              | ⚠️     | Fluent ->note() works; PHPDoc does not.       |

## 📄 License
The MIT License (MIT). Please see [License File](./LICENSE) for more information.
