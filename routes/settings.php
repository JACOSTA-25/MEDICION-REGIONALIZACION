<?php

use App\Http\Controllers\Perfil\PerfilController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [PerfilController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('settings/profile', [PerfilController::class, 'update'])
        ->name('profile.update');

    Route::delete('settings/profile', [PerfilController::class, 'destroy'])
        ->name('profile.destroy');
});
