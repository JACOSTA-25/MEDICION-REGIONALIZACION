<?php

use App\Http\Controllers\Encuesta\CodigoQrEncuestaController;
use App\Http\Controllers\Encuesta\EncuestaController;
use App\Http\Controllers\Estadisticas\DatosEstadisticasController;
use App\Http\Controllers\Estadisticas\PaginaEstadisticasController;
use App\Http\Controllers\Organizacion\DependenciaController;
use App\Http\Controllers\Organizacion\ProcesoController;
use App\Http\Controllers\Organizacion\ServicioController;
use App\Http\Controllers\Panel\PanelController;
use App\Http\Controllers\Reportes\ReporteGeneralController;
use App\Http\Controllers\Reportes\ReporteIndividualController;
use App\Http\Controllers\Reportes\ReporteProcesoController;
use App\Http\Controllers\Usuarios\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/encuesta', [EncuestaController::class, 'create'])->name('survey.create');
Route::get('/encuesta/acceso', [EncuestaController::class, 'access'])
    ->middleware('signed')
    ->name('survey.access');
Route::post('/encuesta', [EncuestaController::class, 'store'])->name('survey.store');
Route::get('/encuesta/catalogos/dependencias', [EncuestaController::class, 'dependencias'])->name('survey.catalogs.dependencias');
Route::get('/encuesta/catalogos/servicios', [EncuestaController::class, 'servicios'])->name('survey.catalogs.servicios');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PanelController::class, 'index'])->name('dashboard');
    Route::put('/dashboard/trimestres', [PanelController::class, 'updateQuarters'])->name('dashboard.quarters.update');
    Route::get('/encuesta/qr', [CodigoQrEncuestaController::class, 'show'])->name('survey.qr');

    Route::prefix('reportes')->name('reports.')->group(function () {
        Route::get('general', [ReporteGeneralController::class, 'index'])
            ->middleware('module.access:general_reports')
            ->name('general');
        Route::post('general/conclusion', [ReporteGeneralController::class, 'generateConclusion'])
            ->middleware('module.access:general_reports')
            ->name('general.conclusion');

        Route::get('proceso', [ReporteProcesoController::class, 'index'])
            ->middleware('module.access:process_reports')
            ->name('process');
        Route::post('proceso/conclusion', [ReporteProcesoController::class, 'generateConclusion'])
            ->middleware('module.access:process_reports')
            ->name('process.conclusion');

        Route::get('individual', [ReporteIndividualController::class, 'index'])
            ->middleware('module.access:individual_reports')
            ->name('individual');
        Route::get('individual/servicios', [ReporteIndividualController::class, 'services'])
            ->middleware('module.access:individual_reports')
            ->name('individual.services');
        Route::post('individual/conclusion', [ReporteIndividualController::class, 'generateConclusion'])
            ->middleware('module.access:individual_reports')
            ->name('individual.conclusion');
    });

    Route::prefix('estadisticas')->name('statistics.')->middleware('module.access:statistics')->group(function () {
        Route::get('', [PaginaEstadisticasController::class, 'index'])->name('index');
        Route::get('procesos', [PaginaEstadisticasController::class, 'processes'])
            ->middleware('module.access:statistics_processes')
            ->name('processes');
        Route::get('dependencias', [PaginaEstadisticasController::class, 'dependencies'])
            ->middleware('module.access:statistics_dependencies')
            ->name('dependencies');
        Route::get('servicios', [PaginaEstadisticasController::class, 'services'])
            ->middleware('module.access:statistics_services')
            ->name('services');

        Route::prefix('data')->name('data.')->group(function () {
            Route::get('{level}', [DatosEstadisticasController::class, 'show'])
                ->whereIn('level', ['processes', 'dependencies', 'services'])
                ->name('show');
        });
    });

    Route::prefix('usuarios')->name('users.')->middleware('module.access:users')->group(function () {
        Route::get('', [UsuarioController::class, 'index'])->name('index');
        Route::post('', [UsuarioController::class, 'store'])->name('store');
        Route::put('{user}', [UsuarioController::class, 'update'])->name('update');
        Route::delete('{user}', [UsuarioController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('estructura-organizacional')
        ->name('process-dependency.')
        ->middleware('module.access:process_dependency')
        ->group(function () {
            Route::get('', [ProcesoController::class, 'index'])->name('index');

            Route::get('procesos/{proceso}/dependencias', [DependenciaController::class, 'index'])
                ->name('processes.dependencies');

            Route::post('procesos', [ProcesoController::class, 'store'])
                ->name('processes.store');

            Route::put('procesos/{proceso}', [ProcesoController::class, 'update'])
                ->name('processes.update');

            Route::delete('procesos/{proceso}', [ProcesoController::class, 'deactivate'])
                ->name('processes.deactivate');

            Route::patch('procesos/{proceso}/activar', [ProcesoController::class, 'activate'])
                ->name('processes.activate');

            Route::get('dependencias/{dependencia}/servicios', [ServicioController::class, 'index'])
                ->name('dependencies.services');

            Route::post('dependencias', [DependenciaController::class, 'store'])
                ->name('dependencies.store');

            Route::put('dependencias/{dependencia}', [DependenciaController::class, 'update'])
                ->name('dependencies.update');

            Route::delete('dependencias/{dependencia}', [DependenciaController::class, 'deactivate'])
                ->name('dependencies.deactivate');

            Route::patch('dependencias/{dependencia}/activar', [DependenciaController::class, 'activate'])
                ->name('dependencies.activate');

            Route::post('servicios', [ServicioController::class, 'store'])
                ->name('services.store');

            Route::put('servicios/{servicio}', [ServicioController::class, 'update'])
                ->name('services.update');

            Route::delete('servicios/{servicio}', [ServicioController::class, 'deactivate'])
                ->name('services.deactivate');

            Route::patch('servicios/{servicio}/activar', [ServicioController::class, 'activate'])
                ->name('services.activate');
        });
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
