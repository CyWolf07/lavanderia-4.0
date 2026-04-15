<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PqrsController;
use App\Http\Controllers\PrendaController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecolectorController;
use App\Http\Controllers\RecolectorPrendaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('inicio');

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);

Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'activo'])->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->tieneRol('admin', 'programador')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->tieneRol('recolector')) {
            return redirect()->route('recolector.index');
        }

        return redirect()->route('produccion.index');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('rol:admin,programador,usuario')->prefix('produccion')->group(function () {
        Route::get('/', [ProduccionController::class, 'index'])->name('produccion.index');
        Route::post('/', [ProduccionController::class, 'store'])->name('produccion.store');
    });

    Route::middleware('rol:recolector')->prefix('recolector')->group(function () {
        Route::get('/', [RecolectorController::class, 'index'])->name('recolector.index');
        Route::post('/clientes', [ClienteController::class, 'storeFromRecolector'])->name('recolector.clientes.store');
        Route::post('/facturas', [RecolectorController::class, 'store'])->name('recolector.facturas.store');
    });

    Route::prefix('pqrs')->group(function () {
        Route::get('/', [PqrsController::class, 'index'])->name('pqrs.index');
        Route::post('/', [PqrsController::class, 'store'])->name('pqrs.store');
    });

});

/*
|--------------------------------------------------------------------------
| SOLO ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'activo', 'rol:admin,programador'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/usuarios', [AdminController::class, 'storeUser'])->name('admin.usuarios.store');
    Route::put('/usuarios/{user}', [AdminController::class, 'updateUser'])->name('admin.usuarios.update');
    Route::delete('/usuarios/{user}', [AdminController::class, 'destroyUser'])->name('admin.usuarios.destroy');
    Route::patch('/usuarios/{user}/estado', [AdminController::class, 'toggleUserStatus'])->name('admin.usuarios.toggle-status');
    Route::patch('/usuarios/{user}/permisos-precio', [AdminController::class, 'toggleRecolectorPriceEdit'])->name('admin.usuarios.toggle-precios');

    Route::get('/prendas', [PrendaController::class, 'index'])->name('prendas.index');
    Route::post('/prendas', [PrendaController::class, 'store'])->name('prendas.store');
    Route::put('/prendas/{prenda}', [PrendaController::class, 'update'])->name('prendas.update');
    Route::delete('/prendas/{prenda}', [PrendaController::class, 'destroy'])->name('prendas.destroy');
    Route::patch('/prendas/{prenda}/estado', [PrendaController::class, 'toggleStatus'])->name('prendas.toggle-status');

    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
    Route::patch('/clientes/{cliente}/estado', [ClienteController::class, 'toggleStatus'])->name('clientes.toggle-status');

    Route::get('/recolector-prendas', [RecolectorPrendaController::class, 'index'])->name('recolector-prendas.index');
    Route::post('/recolector-prendas', [RecolectorPrendaController::class, 'store'])->name('recolector-prendas.store');
    Route::put('/recolector-prendas/{recolectorPrenda}', [RecolectorPrendaController::class, 'update'])->name('recolector-prendas.update');
    Route::delete('/recolector-prendas/{recolectorPrenda}', [RecolectorPrendaController::class, 'destroy'])->name('recolector-prendas.destroy');
    Route::patch('/recolector-prendas/{recolectorPrenda}/estado', [RecolectorPrendaController::class, 'toggleStatus'])->name('recolector-prendas.toggle-status');

    Route::post('/produccion/cerrar', [ProduccionController::class, 'cerrar'])->name('produccion.cerrar');
    Route::get('/reportes/{periodo}', [ProduccionController::class, 'reportePeriodo'])
        ->where('periodo', '.*')
        ->name('admin.reportes.periodo');
    Route::get('/reportes-impresion', [AdminController::class, 'printReports'])->name('admin.reportes.impresion');
});

Route::middleware(['auth', 'activo', 'rol:programador'])->group(function () {
    Route::delete('/historial/{historialProduccion}', [AdminController::class, 'destroyHistorial'])
        ->name('programador.historial.destroy');
});
