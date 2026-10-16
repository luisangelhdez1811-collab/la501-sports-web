<?php

use App\Http\Controllers\Api\PublicDataController;
use App\Http\Controllers\Api\Staff\StaffAuthController;
use App\Http\Controllers\Api\Staff\StaffDashboardController;
use App\Http\Controllers\Api\Staff\StaffOrderController;
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

        // Login/logout con PIN — sin middleware de auth, obviamente.
        Route::post('/login', [StaffAuthController::class, 'login'])
            ->name('api.staff.login');

        Route::middleware('staff.auth')->group(function () {
            Route::post('/logout', [StaffAuthController::class, 'logout'])
                ->name('api.staff.logout');

            Route::get('/me', [StaffAuthController::class, 'me'])
                ->name('api.staff.me');
        });

        // Cocina: admin, cocinero y empleado pueden operar la cola.
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

        // Dashboard: solo admin.
        Route::middleware('staff.auth:admin')->group(function () {
            Route::get('/dashboard/today', [StaffDashboardController::class, 'today'])
                ->name('api.staff.dashboard.today');

            Route::get('/dashboard/history', [StaffDashboardController::class, 'history'])
                ->name('api.staff.dashboard.history');
        });
    });