<?php
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ReservaViewController;
use App\Http\Controllers\UsuariosController;
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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';