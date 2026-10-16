<?php

use App\Http\Controllers\Api\Admin\AdminApiController;
use App\Http\Controllers\Api\Customer\AccountController;
use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\Customer\CatalogController;
use App\Http\Controllers\Api\Customer\CheckoutController;
use App\Http\Controllers\Api\Customer\NewsletterController as CustomerNewsletterController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\Customer\ReservationController as CustomerReservationController;
use App\Http\Controllers\Api\PublicDataController;
use App\Http\Controllers\Api\Staff\StaffAuthController;
use App\Http\Controllers\Api\Staff\StaffDashboardController;
use App\Http\Controllers\Api\Staff\StaffOrderController;
use App\Http\Controllers\Api\Waiter\WaiterApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/public')
    ->middleware('throttle:public-api')
    ->controller(PublicDataController::class)
    ->group(function () {
        Route::get('/info', 'info')->name('api.public.info');
        Route::get('/products', 'products')->name('api.public.products');
        Route::get('/categories', 'categories')->name('api.public.categories');
        Route::get('/promotions', 'promotions')->name('api.public.promotions');
        Route::get('/news', 'news')->name('api.public.news');
        Route::get('/search', 'search')->name('api.public.search');
    });

Route::prefix('v1/staff')
    ->middleware('throttle:staff-api')
    ->group(function () {

        Route::post('/login', [StaffAuthController::class, 'login'])
            ->name('api.staff.login');

        Route::middleware('staff.auth')->group(function () {
            Route::post('/logout', [StaffAuthController::class, 'logout'])
                ->name('api.staff.logout');

            Route::get('/me', [StaffAuthController::class, 'me'])
                ->name('api.staff.me');
        });

        Route::middleware('staff.auth:admin,cocinero,empleado')->group(function () {
            Route::get('/orders', [StaffOrderController::class, 'index'])
                ->name('api.staff.orders.index');

            Route::get('/orders/{order}', [StaffOrderController::class, 'show'])
                ->name('api.staff.orders.show');

            Route::patch('/orders/{order}/status', [StaffOrderController::class, 'updateStatus'])
                ->name('api.staff.orders.status');

            Route::post('/orders/{order}/cancel', [StaffOrderController::class, 'cancel'])
                ->name('api.staff.orders.cancel');

            Route::patch('/orders/{order}/items/{item}', [StaffOrderController::class, 'updateItemQuantity'])
                ->name('api.staff.orders.items.update');
        });

        Route::middleware('staff.auth:admin')->group(function () {
            Route::get('/dashboard/today', [StaffDashboardController::class, 'today'])
                ->name('api.staff.dashboard.today');

            Route::get('/dashboard/history', [StaffDashboardController::class, 'history'])
                ->name('api.staff.dashboard.history');
        });
    });

Route::prefix('v1/app')
    ->middleware('throttle:public-api')
    ->group(function () {

        Route::prefix('auth')->controller(CustomerAuthController::class)->group(function () {
            Route::post('/register', 'register')->name('api.app.auth.register');
            Route::post('/login', 'login')->name('api.app.auth.login');
            Route::post('/forgot-password', 'forgotPassword')->name('api.app.auth.forgot');
            Route::post('/reset-password', 'resetPassword')->name('api.app.auth.reset');

            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/logout', 'logout')->name('api.app.auth.logout');
                Route::get('/me', 'me')->name('api.app.auth.me');
            });
        });

        Route::get('/products/{product}', [CatalogController::class, 'show'])->name('api.app.products.show');
        Route::get('/recommendations', [CatalogController::class, 'recommendations'])->name('api.app.recommendations');

        Route::post('/checkout', [CheckoutController::class, 'store'])->name('api.app.checkout.store');

        Route::get('/orders/track/{token}', [CustomerOrderController::class, 'track'])->name('api.app.orders.track');
        Route::post('/orders/track/{token}/confirm-received', [CustomerOrderController::class, 'confirmReceived'])->name('api.app.orders.confirm');
        Route::post('/orders/lookup/request-otp', [CustomerOrderController::class, 'requestLookupOtp'])
            ->middleware('throttle:5,10')
            ->name('api.app.orders.lookup.request');
        Route::post('/orders/lookup/verify-otp', [CustomerOrderController::class, 'verifyLookupOtp'])
            ->middleware('throttle:10,10')
            ->name('api.app.orders.lookup.verify');

        Route::post('/reservations', [CustomerReservationController::class, 'store'])->name('api.app.reservations.store');

        Route::post('/newsletter/subscribe', [CustomerNewsletterController::class, 'subscribe'])->name('api.app.newsletter.subscribe');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/orders', [CustomerOrderController::class, 'mine'])->name('api.app.orders.mine');

            Route::prefix('account')->controller(AccountController::class)->group(function () {
                Route::get('/', 'profile')->name('api.app.account.show');
                Route::put('/', 'update')->name('api.app.account.update'); // Rectificación (ARCO)
                Route::delete('/', 'destroy')->name('api.app.account.destroy'); // Cancelación (ARCO)
                Route::post('/phone/request-otp', 'requestPhoneOtp')->name('api.app.account.phone.request');
                Route::put('/phone', 'updatePhone')->name('api.app.account.phone');
                Route::put('/password', 'updatePassword')->name('api.app.account.password');
                Route::post('/promo-subscription/toggle', 'togglePromoSubscription')->name('api.app.account.promo.toggle');
            });
        });
    });
    
Route::prefix('v1/app/admin')
    ->middleware(['auth:sanctum', 'role.admin', 'no-store', 'throttle:60,1'])
    ->controller(AdminApiController::class)
    ->group(function () {
        Route::get('/sales/summary', 'salesSummary')->name('api.app.admin.sales.summary');
        Route::get('/sales/report', 'salesReport')->name('api.app.admin.sales.report');

        Route::get('/reservations', 'reservations')->name('api.app.admin.reservations.index');
        Route::put('/reservations/{reservation}', 'updateReservation')->name('api.app.admin.reservations.update');

        Route::get('/messages', 'messages')->name('api.app.admin.messages.index');
        Route::put('/messages/{message}', 'updateMessage')->name('api.app.admin.messages.update');

        Route::get('/waiters/performance', 'waiterPerformance')->name('api.app.admin.waiters.performance');
    });

/*
|--------------------------------------------------------------------------
| v1/app/waiter — Mapa de mesas para la app de mesero
|--------------------------------------------------------------------------
| Solo roles empleado/mesero/admin (ver EnsureWaiterRole). Los montos
| siempre se calculan aquí desde Product — nunca se confía en precios que
| mande la app.
*/
Route::prefix('v1/app/waiter')
    ->middleware(['auth:sanctum', 'role.waiter', 'no-store', 'throttle:60,1'])
    ->controller(WaiterApiController::class)
    ->group(function () {
        Route::get('/tables', 'index')->name('api.app.waiter.tables.index');
        Route::post('/tables', 'store')->name('api.app.waiter.tables.store');
        Route::get('/tables/{table}', 'show')->name('api.app.waiter.tables.show');
        Route::post('/tables/{table}/orders', 'storeOrder')->name('api.app.waiter.tables.orders');
        Route::post('/tables/{table}/charge', 'charge')->name('api.app.waiter.tables.charge');
    });