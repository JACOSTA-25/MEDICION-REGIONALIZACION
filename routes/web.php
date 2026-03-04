<?php

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
});

require __DIR__.'/auth.php';
