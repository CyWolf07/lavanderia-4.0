<?php

/*
|--------------------------------------------------------------------------
| Web Routes — Lavandería Exclusiva
|--------------------------------------------------------------------------
|
| Aquí se definen todas las rutas web de la aplicación.
|
| Middleware disponibles:
|   'auth'       → El usuario debe haber iniciado sesión
|   'activo'     → El usuario debe estar habilitado (activo = true)
|   'rol:x,y'   → El usuario debe tener uno de los roles especificados
|
| Roles del sistema:
|   - usuario        → Solo puede registrar producción propia
|   - recolector     → Gestiona facturas y clientes
|   - admin          → Acceso completo al panel
|   - programador    → Acceso completo + acciones técnicas especiales
|
*/

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
use App\Http\Controllers\EnterpriseAccessController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\MapaClientesController;
use App\Http\Controllers\OrdenPedidoController;
use App\Http\Controllers\PqrsController;
use App\Http\Controllers\PrendaController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecolectorController;
use App\Http\Controllers\RecolectorPrendaController;
use Illuminate\Support\Facades\Route;

// ── RUTA PRINCIPAL ────────────────────────────────────────────────────────────
// Si el usuario ya está logueado, lo redirige al dashboard correspondiente.
// Si no está logueado, muestra la landing page de bienvenida.
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('inicio');

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN (Rutas públicas — sin login requerido)
|--------------------------------------------------------------------------
*/

// Iniciar sesión
Route::get('/codigo-empresarial', [EnterpriseAccessController::class, 'create'])->name('enterprise-code.create');
Route::post('/codigo-empresarial', [EnterpriseAccessController::class, 'store'])->name('enterprise-code.store');
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

// Registro de nuevos usuarios (puede estar deshabilitado en producción)
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);

// Recuperación de contraseña olvidada
Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

// Restablecer contraseña con token enviado por correo
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');

// Cerrar sesión
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Requieren: auth + activo)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'activo'])->group(function () {

    // Comprobante individual disponible para administrador, programador y dueño recolector.
    Route::get('/ordenes/{facturaRecolector}/imprimir', [OrdenPedidoController::class, 'imprimir'])
        ->middleware('rol:admin,programador,recolector')
        ->name('ordenes.imprimir');

    // ── DASHBOARD ──────────────────────────────────────────────────────────────
    // Redirige al módulo correcto según el rol del usuario autenticado
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->tieneRol('admin', 'programador')) {
            return redirect()->route('admin.dashboard');    // Panel administrativo
        }

        if ($user->tieneRol('recolector')) {
            return redirect()->route('recolector.index');   // Módulo de facturas
        }

        return redirect()->route('produccion.index');       // Registro de producción personal
    })->name('dashboard');

    // ── PERFIL DEL USUARIO ─────────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── VERIFICACIÓN DE EMAIL ──────────────────────────────────────────────────
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // ── CAMBIO DE CONTRASEÑA ───────────────────────────────────────────────────
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    // ── MÓDULO PRODUCCIÓN ──────────────────────────────────────────────────────
    // Roles: usuario (solo registro propio), admin y programador (con totales)
    Route::middleware('rol:admin,programador,usuario')->prefix('produccion')->group(function () {
        Route::get('/', [ProduccionController::class, 'index'])->name('produccion.index'); // Ver registros propios + formulario
        Route::post('/', [ProduccionController::class, 'store'])->name('produccion.store'); // Guardar nuevo registro
        Route::patch('/ordenes/{facturaRecolector}/lavado', [ProduccionController::class, 'guardarLavado'])->name('produccion.ordenes.lavado');
    });

    // ── MÓDULO RECOLECTOR ──────────────────────────────────────────────────────
    // Solo para el rol 'recolector'. Gestiona facturas de entrega de ropa.
    Route::middleware(['rol:recolector', 'throttle:60,1'])->prefix('recolector')->group(function () {
        Route::get('/', [RecolectorController::class,  'index'])->name('recolector.index');         // Ver facturas y formulario
        Route::post('/clientes', [ClienteController::class,     'storeFromRecolector'])->name('recolector.clientes.store'); // Crear cliente rápido
        Route::post('/facturas', [RecolectorController::class,  'store'])->name('recolector.facturas.store'); // Guardar factura nueva
        Route::patch('/facturas/{facturaRecolector}/estatus', [RecolectorController::class, 'updateFacturaEstado'])->name('recolector.facturas.estatus');
        Route::post('/gastos', [GastoController::class,       'storeFromRecolector'])->name('recolector.gastos.store'); // Guardar gasto de quincena
    });

    // ── MÓDULO PQRS (Peticiones, Quejas, Reclamos y Sugerencias) ──────────────
    // Accesible para cualquier usuario autenticado y activo
    Route::prefix('pqrs')->group(function () {
        Route::get('/', [PqrsController::class, 'index'])->name('pqrs.index');     // Listado + formulario de radicación
        Route::post('/', [PqrsController::class, 'store'])->name('pqrs.store');     // Guardar nuevo PQRS
        // Edit y delete sólo para admin/programador
        Route::middleware('rol:admin,programador')->group(function () {
            Route::get('/{id}/edit', [PqrsController::class, 'edit'])->name('pqrs.edit');
            Route::put('/{id}', [PqrsController::class, 'update'])->name('pqrs.update');
            Route::delete('/{id}', [PqrsController::class, 'destroy'])->name('pqrs.destroy');
        });
    });

});

/*
|--------------------------------------------------------------------------
| SOLO ADMIN / PROGRAMADOR (Requieren: auth + activo + rol:admin,programador)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'activo', 'rol:admin,programador', 'throttle.admin'])->prefix('admin')->group(function () {

    // ── PANEL PRINCIPAL ────────────────────────────────────────────────────────
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // ── GESTIÓN DE USUARIOS ────────────────────────────────────────────────────
    Route::post('/usuarios', [AdminController::class, 'storeUser'])->name('admin.usuarios.store');
    Route::put('/usuarios/{user}', [AdminController::class, 'updateUser'])->name('admin.usuarios.update');
    Route::delete('/usuarios/{user}', [AdminController::class, 'destroyUser'])->name('admin.usuarios.destroy');
    Route::patch('/usuarios/{user}/estado', [AdminController::class, 'toggleUserStatus'])->name('admin.usuarios.toggle-status');
    Route::patch('/usuarios/{user}/permisos-precio', [AdminController::class, 'toggleRecolectorPriceEdit'])->name('admin.usuarios.toggle-precios');

    // ── GESTIÓN DE PRENDAS (producción) ───────────────────────────────────────
    Route::get('/prendas', [PrendaController::class, 'index'])->name('prendas.index');
    Route::post('/prendas', [PrendaController::class, 'store'])->name('prendas.store');
    Route::put('/prendas/{prenda}', [PrendaController::class, 'update'])->name('prendas.update');
    Route::delete('/prendas/{prenda}', [PrendaController::class, 'destroy'])->name('prendas.destroy');
    Route::patch('/prendas/{prenda}/estado', [PrendaController::class, 'toggleStatus'])->name('prendas.toggle-status');

    // ── GESTIÓN DE CLIENTES ────────────────────────────────────────────────────
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
    Route::patch('/clientes/{cliente}/estado', [ClienteController::class, 'toggleStatus'])->name('clientes.toggle-status');
    Route::patch('/clientes/{cliente}/delegar', [ClienteController::class, 'delegarRecolector'])->name('clientes.delegar');

    // ── MAPA DE CLIENTES ───────────────────────────────────────────────────────
    Route::get('/mapa-clientes', [MapaClientesController::class, 'index'])->name('admin.mapa-clientes');
    Route::patch('/mapa-clientes/{cliente}/coordenadas', [MapaClientesController::class, 'updateCoordenadas'])->name('admin.mapa-clientes.coordenadas');
    Route::get('/mapa-clientes/geocodificar', [MapaClientesController::class, 'geocodificar'])->name('admin.mapa-clientes.geocodificar');

    // ── GESTIÓN DE PRENDAS DEL RECOLECTOR ─────────────────────────────────────
    // Prendas exclusivas para el módulo de recolección (independientes de producción)
    Route::get('/recolector-prendas', [RecolectorPrendaController::class, 'index'])->name('recolector-prendas.index');
    Route::post('/recolector-prendas', [RecolectorPrendaController::class, 'store'])->name('recolector-prendas.store');
    Route::put('/recolector-prendas/{recolectorPrenda}', [RecolectorPrendaController::class, 'update'])->name('recolector-prendas.update');
    Route::delete('/recolector-prendas/{recolectorPrenda}', [RecolectorPrendaController::class, 'destroy'])->name('recolector-prendas.destroy');
    Route::patch('/recolector-prendas/{recolectorPrenda}/estado', [RecolectorPrendaController::class, 'toggleStatus'])->name('recolector-prendas.toggle-status');

    // ── CIERRE DE QUINCENA Y REPORTES ─────────────────────────────────────────
    Route::delete('/produccion/bulk-delete', [AdminController::class, 'destroyProducciones'])->name('admin.produccion.destroy-bulk');
    Route::delete('/produccion/{produccion}', [AdminController::class, 'destroyProduccion'])->name('admin.produccion.destroy');
    Route::get('/produccion/{produccion}/edit', [AdminController::class, 'editProduccion'])->name('admin.produccion.edit');
    Route::put('/produccion/{produccion}', [AdminController::class, 'updateProduccion'])->name('admin.produccion.update');

    Route::delete('/facturas-recolector/{facturaRecolector}', [AdminController::class, 'destroyFacturaRecolector'])->name('admin.facturas-recolector.destroy');
    Route::get('/facturas-recolector/{facturaRecolector}/edit', [AdminController::class, 'editFacturaRecolector'])->name('admin.facturas-recolector.edit');
    Route::put('/facturas-recolector/{facturaRecolector}', [AdminController::class, 'updateFacturaRecolector'])->name('admin.facturas-recolector.update');
    Route::patch('/facturas-recolector/{facturaRecolector}/estatus', [AdminController::class, 'updateFacturaEstado'])->name('admin.facturas-recolector.estatus');

    Route::delete('/historial/{historialProduccion}', [AdminController::class, 'destroyHistorial'])->name('admin.historial.destroy');
    Route::get('/historial/{historialProduccion}/edit', [ProduccionController::class, 'editHistorial'])->name('admin.historial.edit');
    Route::put('/historial/{historialProduccion}', [ProduccionController::class, 'updateHistorial'])->name('admin.historial.update');
    Route::post('/produccion/cerrar', [ProduccionController::class, 'cerrar'])->name('produccion.cerrar');
    Route::get('/reportes/{periodo}', [ProduccionController::class, 'reportePeriodo'])
        ->where('periodo', '.*')              // Permite el formato AÑO/MES/QUINCENA con slash
        ->name('admin.reportes.periodo');
    Route::get('/reportes-impresion', [AdminController::class, 'printReports'])->name('admin.reportes.impresion');
    Route::post('/gastos', [GastoController::class, 'storeFromAdmin'])->name('admin.gastos.store');
    Route::get('/incongruencias', [AdminController::class, 'incongruencias'])->name('admin.incongruencias.index');
    Route::patch('/notificaciones/{notificationId}/leer', [AdminController::class, 'markNotificationAsRead'])->name('admin.notificaciones.read');
    Route::patch('/dispositivos-bloqueados/{accessControl}/desbloquear', [EnterpriseAccessController::class, 'unlock'])->name('admin.dispositivos.unlock');
    Route::post('/codigo-empresarial/regenerar', [EnterpriseAccessController::class, 'regenerate'])->name('admin.codigo-empresarial.regenerate');

});

/*
|--------------------------------------------------------------------------
| SOLO PROGRAMADOR (Acciones técnicas críticas)
|--------------------------------------------------------------------------
| Nota: la eliminación de historial ya está cubierta por el grupo admin
| (rol:admin,programador). No se duplica aquí.
|--------------------------------------------------------------------------
*/
