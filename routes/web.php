<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::view('/reportes/general', 'modules.reportes-general')
        ->middleware('module.access:general_reports')
        ->name('reports.general');

    Route::view('/reportes/proceso', 'modules.reportes-proceso')
        ->middleware('module.access:process_reports')
        ->name('reports.process');

    Route::view('/reportes/individual', 'modules.reportes-individual')
        ->middleware('module.access:individual_reports')
        ->name('reports.individual');

    Route::view('/usuarios', 'modules.usuarios')
        ->middleware('module.access:users')
        ->name('users.index');
});

require __DIR__.'/auth.php';
