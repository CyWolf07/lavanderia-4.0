<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrendaController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PqrsController;

Route::get('/', function () {
    return view('welcome');
});

// Since we haven't installed full Auth scaffolding yet, we can protect routes with the 'auth' middleware once users can log in, or keep them open for testing.
// For now, let's keep them accessible to demonstrate functionality.
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
});

Route::prefix('prendas')->group(function () {
    Route::get('/', [PrendaController::class, 'index'])->name('prendas.index');
    Route::post('/', [PrendaController::class, 'store'])->name('prendas.store');
    Route::put('/{prenda}', [PrendaController::class, 'update'])->name('prendas.update');
    Route::delete('/{prenda}', [PrendaController::class, 'destroy'])->name('prendas.destroy');
});

Route::prefix('produccion')->group(function () {
    Route::get('/', [ProduccionController::class, 'index'])->name('produccion.index');
    Route::post('/', [ProduccionController::class, 'store'])->name('produccion.store');
});

Route::prefix('pqrs')->group(function () {
    Route::get('/', [PqrsController::class, 'index'])->name('pqrs.index');
    Route::post('/', [PqrsController::class, 'store'])->name('pqrs.store');
});
