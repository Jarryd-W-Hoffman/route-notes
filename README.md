# Laravel Route Notes

Add human‑readable notes to your Laravel routes and surface them directly in `php artisan route:list`.

This package is intentionally small, zero‑config, and DX‑first. It helps teams understand *why* a route exists, who it’s for, or any special behaviour — without digging through controllers or docs.

---

## ✨ Features

* ✅ Attach notes fluently to routes
* ✅ Support **multiple notes per route**
* ✅ `notes([...])` alias for array lovers
* ✅ PHPDoc `@note` support on controller methods
* ✅ Invokable controller support
* ✅ `--notes` CLI flag to filter routes
* ✅ Route‑cache safe
* ✅ Zero configuration

---

## 📦 Installation

```bash
composer require vendor/laravel-route-notes
```

That’s it. No config, no publishing, no setup.

---

## 🚀 Basic Usage

Attach a note to any route:

```php
Route::get('/users', [UserController::class, 'index'])
    ->note('Admin only');
```

Run:

```bash
php artisan route:list
```

You’ll see a new **Note** column:

```
GET|HEAD users users.index Admin only
```

---

## 📝 Multiple Notes

You can attach multiple notes to a single route:

```php
Route::get('/reports', fn () => ...)
    ->note('Admin only')
    ->note('Rate limited')
    ->note('Audited');
```

Output:

```
Admin only | Rate limited | Audited
```

Order is preserved.

---

## 🧩 `notes([...])` Alias

If you prefer arrays, use the `notes()` alias:

```php
Route::get('/reports', fn () => ...)
    ->notes([
        'Admin only',
        'Rate limited',
        'Audited',
    ]);
```

This is functionally identical to chaining `->note()` calls and can be mixed freely:

```php
->notes(['Admin only'])->note('Audited');
```

---

## 📚 PHPDoc `@note` Support

You can also define notes directly in your controller’s PHPDoc.

### Controller methods

```php
class UserController
{
    /**
     * @note Admin only
     * @note Rate limited
     */
    public function index()
    {
        //
    }
}
```

```php
Route::get('/users', [UserController::class, 'index']);
```

These notes will automatically appear in `route:list`.

---

## 🎯 Invokable Controllers

Invokable controllers are fully supported.

```php
Route::post('/reports', ReportController::class);
```

```php
class ReportController
{
    /**
     * @note Admin only
     * @note Writes to ledger
     */
    public function __invoke()
    {
        //
    }
}
```

Output:

```
Admin only | Writes to ledger
```

---

## 🔀 Combining Fluent + PHPDoc Notes

Fluent notes and PHPDoc notes are merged together:

```php
Route::get('/users', [UserController::class, 'index'])
    ->note('Audited');
```

```php
/** @note Admin only */
public function index() {}
```

Output:

```
Audited | Admin only
```

Fluent notes always appear first.

---

## 🔎 Filtering Routes with Notes

Use the `--notes` flag to show **only routes that have notes**:

```bash
php artisan route:list --notes
```

Perfect for auditing sensitive or internal routes.

---

## ⚠️ Supported & Unsupported Cases

### Supported

* Controller actions (`Controller@method`)
* Invokable controllers (`__invoke`)
* Route caching

### Not supported (by design)

* Closure PHPDoc notes
* Class‑level `@note` annotations

These can be added later without breaking changes if needed.

---

## 🤔 Why This Exists

Routes often carry important *context*:

* Who is allowed to use this?
* Is it rate limited?
* Is it internal or public?
* Is it safe to deprecate?

Laravel already documents **what** routes do — this package helps document **why**.

---

## 📄 License

MIT
