<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Organization\DependencyController;
use App\Http\Controllers\Organization\ProcessController;
use App\Http\Controllers\Organization\ServiceController;
use App\Http\Controllers\Reports\GeneralReportController;
use App\Http\Controllers\Reports\IndividualReportController;
use App\Http\Controllers\Reports\ProcessReportController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/encuesta', [SurveyController::class, 'create'])->name('survey.create');
Route::get('/encuesta/acceso', [SurveyController::class, 'access'])
    ->middleware('signed')
    ->name('survey.access');
Route::post('/encuesta', [SurveyController::class, 'store'])->name('survey.store');
Route::get('/encuesta/catalogos/dependencias', [SurveyController::class, 'dependencias'])->name('survey.catalogs.dependencias');
Route::get('/encuesta/catalogos/servicios', [SurveyController::class, 'servicios'])->name('survey.catalogs.servicios');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/dashboard/trimestres', [DashboardController::class, 'updateQuarters'])->name('dashboard.quarters.update');

    Route::prefix('reportes')->name('reports.')->group(function () {
        Route::get('general', [GeneralReportController::class, 'index'])
            ->middleware('module.access:general_reports')
            ->name('general');

        Route::get('proceso', [ProcessReportController::class, 'index'])
            ->middleware('module.access:process_reports')
            ->name('process');

        Route::get('individual', [IndividualReportController::class, 'index'])
            ->middleware('module.access:individual_reports')
            ->name('individual');
    });

    Route::prefix('usuarios')->name('users.')->middleware('module.access:users')->group(function () {
        Route::get('', [UserController::class, 'index'])->name('index');
        Route::post('', [UserController::class, 'store'])->name('store');
        Route::put('{user}', [UserController::class, 'update'])->name('update');
    });

    Route::prefix('estructura-organizacional')
        ->name('process-dependency.')
        ->middleware('module.access:process_dependency')
        ->group(function () {
            Route::get('', [ProcessController::class, 'index'])->name('index');

            Route::get('procesos/{proceso}/dependencias', [DependencyController::class, 'index'])
                ->name('processes.dependencies');

            Route::post('procesos', [ProcessController::class, 'store'])
                ->name('processes.store');

            Route::put('procesos/{proceso}', [ProcessController::class, 'update'])
                ->name('processes.update');

            Route::delete('procesos/{proceso}', [ProcessController::class, 'deactivate'])
                ->name('processes.deactivate');

            Route::patch('procesos/{proceso}/activar', [ProcessController::class, 'activate'])
                ->name('processes.activate');

            Route::get('dependencias/{dependencia}/servicios', [ServiceController::class, 'index'])
                ->name('dependencies.services');

            Route::post('dependencias', [DependencyController::class, 'store'])
                ->name('dependencies.store');

            Route::put('dependencias/{dependencia}', [DependencyController::class, 'update'])
                ->name('dependencies.update');

            Route::delete('dependencias/{dependencia}', [DependencyController::class, 'deactivate'])
                ->name('dependencies.deactivate');

            Route::patch('dependencias/{dependencia}/activar', [DependencyController::class, 'activate'])
                ->name('dependencies.activate');

            Route::post('servicios', [ServiceController::class, 'store'])
                ->name('services.store');

            Route::put('servicios/{servicio}', [ServiceController::class, 'update'])
                ->name('services.update');

            Route::delete('servicios/{servicio}', [ServiceController::class, 'deactivate'])
                ->name('services.deactivate');

            Route::patch('servicios/{servicio}/activar', [ServiceController::class, 'activate'])
                ->name('services.activate');
        });
});

require __DIR__.'/auth.php';
