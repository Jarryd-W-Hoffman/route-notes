<?php

/*
 * A small demo app for the README screenshots. The controllers live in the
 * application's controller namespace so route:list shortens them as it would
 * in a real app.
 */

namespace App\Http\Controllers;

use Jarryd\RouteNotes\Note;
use Jarryd\RouteNotes\NoteLevel;

class DashboardController
{
    public function __invoke() {}
}

class SyncController
{
    public function handle() {}
}

class StripeWebhookController
{
    public function __invoke() {}
}

class UserController
{
    /**
     * @note Used by the admin portal only
     * @note Rate limited to 5 requests a minute
     */
    public function index() {}

    public function show() {}

    #[Note('Hard deletes the customer and their invoices', level: NoteLevel::Danger)]
    public function destroy() {}
}

/**
 * @note Owned by the finance team
 */
class LedgerController
{
    #[Note('Writes to the ledger')]
    public function store() {}
}

class OrderController
{
    /**
     * @note [deprecated] Use /api/v2/orders, which pages its results
     */
    public function index() {}

    public function indexV2() {}
}

class HealthController
{
    public function __invoke() {}
}

namespace Jarryd\RouteNotes\Tests\Screenshots;

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

class DemoApp
{
    public static function routes(): void
    {
        Route::get('/', Controllers\DashboardController::class)->name('dashboard');

        Route::get('/api/legacy-sync', [Controllers\SyncController::class, 'handle'])
            ->name('sync.handle')
            ->note('Legacy sync for the old ERP')
            ->note('Scheduled for removal in Q4', 'deprecated');

        Route::post('/webhooks/stripe', Controllers\StripeWebhookController::class)
            ->note('Production only')
            ->note('Requires the Stripe signing secret', 'warning');

        Route::get('/users', [Controllers\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [Controllers\UserController::class, 'show'])->name('users.show');
        Route::delete('/users/{user}', [Controllers\UserController::class, 'destroy'])->name('users.destroy');

        Route::post('/ledger', [Controllers\LedgerController::class, 'store'])->name('ledger.store');

        Route::get('/api/v1/orders', [Controllers\OrderController::class, 'index']);
        Route::get('/api/v2/orders', [Controllers\OrderController::class, 'indexV2']);

        Route::get('/up', Controllers\HealthController::class);
    }
}
