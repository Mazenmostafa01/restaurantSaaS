<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;

Route::get('/', function() {
    return view('welcome');
})->name('home');

Route::prefix('items')->group(function() {
    Route::get('/create', [ItemController::class, 'create'])->name('items.create');
    Route::get('/show/{item}', [ItemController::class, 'show'])->name('items.show');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::delete('/show/{item}', [ItemController::class, 'delete'])->name('items.delete');
});