<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'La confirmacion de la nueva contrasena no coincide.',
            'password.min' => 'La nueva contrasena debe tener minimo 8 caracteres.',
        ]);

        $request->user()->update([
            'password_hash' => Hash::make($validated['password']),
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('password_update_notice', [
                'title' => 'Contrasena actualizada correctamente',
                'message' => 'Por seguridad, cerramos tu sesion. Ingresa nuevamente con tu nueva contrasena para continuar.',
            ]);
    }
}
