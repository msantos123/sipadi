<?php
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ReservaViewController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\EstanciaController;
use App\Http\Controllers\LoteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('usuarios/index', [UsuariosController::class, 'index'])->middleware(['auth', 'verified'])->name('usuarios.index');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/create', [UsuariosController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UsuariosController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}/edit', [UsuariosController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{id}', [UsuariosController::class, 'update'])->name('usuarios.update');

    Route::get('/reservas/nueva', [ReservaViewController::class, 'create'])->name('reservas.create');

    // Ruta para la búsqueda de personas
    Route::get('/personas/search', [PersonaController::class, 'search'])->name('personas.search');
    Route::post('/personas', [PersonaController::class, 'store'])->name('personas.store');
    Route::put('/personas/{persona}', [PersonaController::class, 'update'])->name('personas.update');

    Route::get('/checkin', [CheckinController::class, 'create'])->name('checkin.create');
    Route::post('/checkin', [CheckinController::class, 'store'])->name('checkin.store');

    // Rutas para Estancias
    Route::get('/estancias', [CheckinController::class, 'index'])->name('estancias.index');
    Route::put('/estancias/{estancia}/checkout', [EstanciaController::class, 'checkout'])->name('estancias.checkout');
    Route::put('/estancias/{estancia}/cancel', [EstanciaController::class, 'cancel'])->name('estancias.cancel');

    // Rutas para la aprobación de GAD
    Route::put('/estancias/{estancia}/aprobar-gad', [EstanciaController::class, 'aprobarGad'])->name('estancias.aprobar-gad');
    Route::put('/estancias/{estancia}/rechazar-gad', [EstanciaController::class, 'rechazarGad'])->name('estancias.rechazar-gad');

    // Rutas para Lotes
    Route::get('/revision', [LoteController::class, 'revisionView'])->name('lotes.revision-view');
    Route::put('/lotes/{lote}/enviar-gad', [LoteController::class, 'submitToGad'])->name('lotes.submit-gad');
    Route::get('/lotes/revision/gad', [LoteController::class, 'revisionGad'])->name('lotes.revision-gad');
    Route::get('/lotes/{lote}/estancias', [LoteController::class, 'getEstancias'])->name('lotes.estancias');
    Route::put('/lotes/{lote}/enviar-vmt', [LoteController::class, 'submitToVmt'])->name('lotes.submit-vmt');

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
