<?php

use App\Http\Controllers\Api\Customer\CustomerSpaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dashboard\DashBoardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SaaS\SaaSAuthController;
use App\Http\Controllers\SaaS\SaaSRestaurantAdminController;
use App\Http\Controllers\SaaS\SaaSRestaurantController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('loginPost');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'updatePassword'])->name('password.change.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/', function () {
            return view('welcome');
        })->name('home');

        Route::get('/dashboard', DashBoardController::class)
            ->middleware('role:Admin')
            ->name('dashboard');

        Route::prefix('items')->name('items.')->group(function () {
            Route::get('/index', [ItemController::class, 'index'])->name('index');
            Route::get('/create', [ItemController::class, 'create'])->name('create');
            Route::post('/items', [ItemController::class, 'store'])->name('store');
            Route::get('/show/{item}', [ItemController::class, 'show'])->name('show');

            Route::middleware('role:Admin')->group(function () {
                Route::get('/edit/{item}', [ItemController::class, 'edit'])->name('edit');
                Route::put('/{item}', [ItemController::class, 'update'])->name('update');
                Route::delete('/delete/{item}', [ItemController::class, 'delete'])->name('delete');
                Route::post('/restore/{item}', [ItemController::class, 'restore'])->name('restore');
            });
        });

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/index', [OrderController::class, 'index'])->name('index');
            Route::get('/search-customers', [OrderController::class, 'searchCustomers'])->name('search-customers');
            Route::post('/quick-customer', [OrderController::class, 'quickStoreCustomer'])->name('quick-customer');
            Route::get('/create', [OrderController::class, 'create'])->name('create');
            Route::post('/orders', [OrderController::class, 'store'])->name('store');

            Route::middleware('role:Admin')->group(function () {
                Route::get('/edit/{order}', [OrderController::class, 'edit'])->name('edit');
                Route::put('/update/{order}', [OrderController::class, 'update'])->name('update');
                Route::delete('delete/{order}', [OrderController::class, 'delete'])->name('delete');
            });
        });

        Route::middleware('role:Admin')->prefix('employees')->name('employees.')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('index');
            Route::get('/create', [EmployeeController::class, 'create'])->name('create');
            Route::post('/', [EmployeeController::class, 'store'])->name('store');
            Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
            Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
            Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
        });
    });
});

// ── SaaS Business Owner Portal ───────────────────────────────────────────────
Route::prefix('saas')->name('saas.')->group(function () {
    Route::get('/login', [SaaSAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [SaaSAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.post');

    Route::middleware('auth:business_owner')->group(function () {
        Route::post('/logout', [SaaSAuthController::class, 'logout'])->name('logout');
        Route::get('/', fn () => redirect()->route('saas.restaurants.index'))->name('dashboard');

        Route::get('/restaurants', [SaaSRestaurantController::class, 'index'])->name('restaurants.index');
        Route::get('/restaurants/create', [SaaSRestaurantController::class, 'create'])->name('restaurants.create');
        Route::post('/restaurants', [SaaSRestaurantController::class, 'store'])->name('restaurants.store');
        Route::get('/restaurants/{restaurant}/edit', [SaaSRestaurantController::class, 'edit'])->name('restaurants.edit');
        Route::put('/restaurants/{restaurant}', [SaaSRestaurantController::class, 'update'])->name('restaurants.update');
        Route::patch('/restaurants/{restaurant}/toggle-active', [SaaSRestaurantController::class, 'toggleActive'])->name('restaurants.toggle-active');
        Route::delete('/restaurants/{restaurant}', [SaaSRestaurantController::class, 'destroy'])->name('restaurants.destroy');
        Route::post('/restaurants/{restaurant}/restore', [SaaSRestaurantController::class, 'restore'])
            ->withTrashed()
            ->name('restaurants.restore');
        Route::delete('/restaurants/{restaurant}/force', [SaaSRestaurantController::class, 'forceDestroy'])
            ->withTrashed()
            ->name('restaurants.force-destroy');

        Route::post('/restaurants/{restaurant}/admins', [SaaSRestaurantAdminController::class, 'store'])
            ->name('restaurants.admins.store');
        Route::put('/restaurants/{restaurant}/admins/{user}/password', [SaaSRestaurantAdminController::class, 'updatePassword'])
            ->scopeBindings()
            ->name('restaurants.admins.reset-password');
    });
});

// ── Customer SPA (catch-all) ─────────────────────────────────────────────────
// Must be LAST so it doesn't intercept admin routes like /login, /dashboard, etc.
Route::get('/{restaurant:slug}/{any?}', CustomerSpaController::class)
    ->where('any', '.*')
    ->name('customer.spa');
