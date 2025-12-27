<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dashboard\DashBoardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('loginPost');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('/dashboard', DashBoardController::class)->name('dashboard');

    Route::prefix('items')->name('items.')->group(function () {
        Route::get('/index', [ItemController::class, 'index'])->name('index');
        Route::get('/create', [ItemController::class, 'create'])->name('create');
        Route::post('/items', [ItemController::class, 'store'])->name('store');
        Route::get('/show/{item}', [ItemController::class, 'show'])->name('show');
        Route::get('/edit/{item}', [ItemController::class, 'edit'])->name('edit');
        Route::put('/{item}', [ItemController::class, 'update'])->name('update');
        Route::delete('/delete/{item}', [ItemController::class, 'delete'])->name('delete');
        Route::post('/restore/{item}', [ItemController::class, 'restore'])->name('restore');
    });

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/index', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/orders', [OrderController::class, 'store'])->name('store');
        Route::get('/edit/{order}', [OrderController::class, 'edit'])->name('edit');
        Route::put('/update/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('delete/{order}', [OrderController::class, 'delete'])->name('delete');
    });
});
