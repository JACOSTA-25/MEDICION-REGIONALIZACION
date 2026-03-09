<?php

use App\Http\Controllers\ProcessDependencyManagementController;
use App\Http\Controllers\ReportModuleController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\UserManagementController;
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
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/reportes/general', [ReportModuleController::class, 'general'])
        ->middleware('module.access:general_reports')
        ->name('reports.general');

    Route::get('/reportes/proceso', [ReportModuleController::class, 'process'])
        ->middleware('module.access:process_reports')
        ->name('reports.process');

    Route::get('/reportes/individual', [ReportModuleController::class, 'individual'])
        ->middleware('module.access:individual_reports')
        ->name('reports.individual');

    Route::get('/usuarios', [UserManagementController::class, 'index'])
        ->middleware('module.access:users')
        ->name('users.index');

    Route::post('/usuarios', [UserManagementController::class, 'store'])
        ->middleware('module.access:users')
        ->name('users.store');

    Route::put('/usuarios/{user}', [UserManagementController::class, 'update'])
        ->middleware('module.access:users')
        ->name('users.update');

    Route::get('/estructura-organizacional', [ProcessDependencyManagementController::class, 'index'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.index');

    Route::get('/estructura-organizacional/procesos/{proceso}/dependencias', [ProcessDependencyManagementController::class, 'dependencies'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.processes.dependencies');

    Route::get('/estructura-organizacional/dependencias/{dependencia}/servicios', [ProcessDependencyManagementController::class, 'services'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.dependencies.services');

    Route::post('/estructura-organizacional/procesos', [ProcessDependencyManagementController::class, 'storeProcess'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.processes.store');

    Route::put('/estructura-organizacional/procesos/{proceso}', [ProcessDependencyManagementController::class, 'updateProcess'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.processes.update');

    Route::delete('/estructura-organizacional/procesos/{proceso}', [ProcessDependencyManagementController::class, 'deactivateProcess'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.processes.deactivate');

    Route::patch('/estructura-organizacional/procesos/{proceso}/activar', [ProcessDependencyManagementController::class, 'activateProcess'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.processes.activate');

    Route::post('/estructura-organizacional/dependencias', [ProcessDependencyManagementController::class, 'storeDependency'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.dependencies.store');

    Route::put('/estructura-organizacional/dependencias/{dependencia}', [ProcessDependencyManagementController::class, 'updateDependency'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.dependencies.update');

    Route::delete('/estructura-organizacional/dependencias/{dependencia}', [ProcessDependencyManagementController::class, 'deactivateDependency'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.dependencies.deactivate');

    Route::patch('/estructura-organizacional/dependencias/{dependencia}/activar', [ProcessDependencyManagementController::class, 'activateDependency'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.dependencies.activate');

    Route::post('/estructura-organizacional/servicios', [ProcessDependencyManagementController::class, 'storeService'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.services.store');

    Route::put('/estructura-organizacional/servicios/{servicio}', [ProcessDependencyManagementController::class, 'updateService'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.services.update');

    Route::delete('/estructura-organizacional/servicios/{servicio}', [ProcessDependencyManagementController::class, 'deactivateService'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.services.deactivate');

    Route::patch('/estructura-organizacional/servicios/{servicio}/activar', [ProcessDependencyManagementController::class, 'activateService'])
        ->middleware('module.access:process_dependency')
        ->name('process-dependency.services.activate');
});

require __DIR__.'/auth.php';
