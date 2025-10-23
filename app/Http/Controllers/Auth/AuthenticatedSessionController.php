<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Sidetur\SideturUser;
use App\Services\SideturSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, SideturSyncService $syncService): RedirectResponse
    {
        Log::info('Iniciando proceso de autenticación para: ' . $request->email);

        // 1. Intentar autenticar contra la base de datos local (SIPADI)
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            Log::info('Autenticación local (SIPADI) exitosa para: ' . $request->email);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        Log::info('Autenticación local fallida. Intentando con Sidetur para: ' . $request->email);

        // 2. Si la autenticación local falla, validar contra Sidetur
        $sideturUser = SideturUser::where('email', $request->email)->first();

        if (! $sideturUser || ! Hash::check($request->password, $sideturUser->password)) {
            Log::warning('Intento de login fallido (credenciales inválidas en SIPADI y Sidetur) para: ' . $request->email);
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        Log::info('Credenciales válidas encontradas en Sidetur para: ' . $request->email);

        // 3. Sincronizar el usuario para obtener/crear el modelo en la BD local (SIPADI)
        $user = $syncService->syncUser($request->email);

        if (! $user) {
            // Esto no debería ocurrir si la validación en Sidetur fue exitosa, pero es una salvaguarda
            Log::error('Error crítico: No se pudo sincronizar el usuario después de una validación exitosa en Sidetur.', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => 'Ocurrió un error al sincronizar la cuenta de usuario.',
            ]);
        }

        // 4. Manejar 2FA y realizar el login para el usuario sincronizado
        if (Features::enabled(Features::twoFactorAuthentication()) && $user->hasEnabledTwoFactorAuthentication()) {
            Log::info('Usuario sincronizado requiere autenticación de dos factores.', ['email' => $user->email]);
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $remember,
            ]);

            return to_route('two-factor.login');
        }

        Log::info('Autenticando y iniciando sesión para el usuario sincronizado desde Sidetur.', ['email' => $user->email]);
        Auth::login($user, $remember);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}