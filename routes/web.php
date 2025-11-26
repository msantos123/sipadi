<?php
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ReservaViewController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\EstanciaController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\EstadisticaController;
use App\Http\Controllers\ConfirmacionController;
use App\Http\Controllers\CsvUploadController;
use App\Http\Controllers\TipoCuartoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\Settings\RoleController;
use Illuminate\Support\Facades\Route;
use Rap2hpoutre\FastExcel\FastExcel;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/solicitudes/{solicitud}/download', [SolicitudController::class, 'download'])->name('solicitudes.download');

Route::get('usuarios/index', [UsuariosController::class, 'index'])->middleware(['auth', 'verified'])->name('usuarios.index');
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/cuartos', [TipoCuartoController::class, 'index'])->name('cuartos.index');
    Route::post('/cuartos', [TipoCuartoController::class, 'store'])->name('cuartos.store');

    Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/create', [UsuariosController::class, 'create'])->name('usuarios.create');
    Route::get('/usuarios/crear-usuario', [UsuariosController::class, 'createUsuario'])->name('usuarios.createUsuario');
    Route::post('/usuarios', [UsuariosController::class, 'store'])->name('usuarios.store');
    Route::post('/usuarios/crear-usuario', [UsuariosController::class, 'storeUsuario'])->name('usuarios.storeUsuario');
    Route::get('/usuarios/{id}/edit', [UsuariosController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{id}', [UsuariosController::class, 'update'])->name('usuarios.update');
    Route::patch('/usuarios/{user}/toggle-estado', [UsuariosController::class, 'toggleEstado'])->name('usuarios.toggleEstado');

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
    Route::post('/lotes/cambiar-estado-multiple', [LoteController::class, 'cambiarEstadoMultiple'])->name('lotes.cambiar-estado-multiple');


    // Rutas para la revisión y confirmación de VMT
    Route::get('/confirmacion', [ConfirmacionController::class, 'index'])->name('confirmacion.index');
    Route::get('/lotes/revision/vmt', [ConfirmacionController::class, 'revisionVmt'])->name('lotes.revision-vmt');
    Route::put('/lotes/{lote}/completar', [ConfirmacionController::class, 'completar'])->name('lotes.completar');
    Route::post('/lotes/completar-multiple', [ConfirmacionController::class, 'completarMultiple'])->name('lotes.completar-multiple');
    Route::put('/estancias/{estancia}/aprobar-vmt', [EstanciaController::class, 'aprobarVmt'])->name('estancias.aprobar-vmt');
    Route::put('/estancias/{estancia}/rechazar-vmt', [EstanciaController::class, 'rechazarVmt'])->name('estancias.rechazar-vmt');



    Route::resource('settings/roles', RoleController::class)->except(['show'])->name('index', 'roles.index');

    Route::get('/solicitud/create', [SolicitudController::class, 'create'])->name('solicitud.create');
    Route::post('/solicitudes', [SolicitudController::class, 'store'])->name('solicitudes.store');
    Route::get('/solicitudes', [SolicitudController::class, 'index'])->name('solicitudes.index');

    Route::get('/csv-upload', [CsvUploadController::class, 'create'])->name('csv.upload.create');

    Route::post('/csv-upload', [CsvUploadController::class, 'store'])->name('csv.upload.store');

    // Rutas para Reportes
    Route::get('/reportes', [\App\Http\Controllers\ReporteController::class, 'index'])->name('reportes.index');
    Route::post('/reporte/generar', [\App\Http\Controllers\ReporteController::class, 'generarReporte'])->name('reporte.generar');
    Route::get('/reporte/generar-excel', [\App\Http\Controllers\ReporteController::class, 'generarExcel'])->name('reporte.excel');

     // Rutas para Estadísticas
    Route::get('/estadisticas', [EstadisticaController::class, 'index'])->name('estadisticas.index');
    Route::post('/estadisticas/generar', [EstadisticaController::class, 'generar'])->name('estadisticas.generar');

    // Rutas para Búsqueda Avanzada
    Route::get('/busqueda', [BusquedaController::class, 'index'])->name('busqueda.index');
    Route::get('/busqueda/search', [BusquedaController::class, 'search'])->name('busqueda.search');

        });


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
