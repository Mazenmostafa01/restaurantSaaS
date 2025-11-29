<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;

Route::get('/', function() {
    return view('welcome');
})->name('home');

Route::prefix('items')->name('items.')->group(function() {
    Route::get('/create', [ItemController::class, 'create'])->name('create');
    Route::post('/items', [ItemController::class, 'store'])->name('store');
    Route::get('/show/{item}', [ItemController::class, 'show'])->name('show');
    Route::get('/edit/{item}', [ItemController::class, 'edit'])->name('edit');
    Route::put('/{item}', [ItemController::class, 'update'])->name('update');
    Route::delete('/delete/{item}', [ItemController::class, 'delete'])->name('delete');
});