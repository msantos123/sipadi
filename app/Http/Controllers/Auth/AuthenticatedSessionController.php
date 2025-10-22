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
        // 1. Validar credenciales contra la base de datos de Sidetur
        Log::info('Iniciando proceso de autenticación para: ' . $request->email);
        $sideturUser = SideturUser::where('email', $request->email)->first();

        if (! $sideturUser || ! Hash::check($request->password, $sideturUser->password)) {
            Log::warning('Intento de login fallido (credenciales inválidas) para: ' . $request->email);
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }
        Log::info('Credenciales válidas encontradas en Sidetur para: ' . $request->email);


        // 2. Sincronizar el usuario y obtener el modelo de la BD local (SIPADI)
        $user = $syncService->syncUser($request->email);

        if (! $user) {
            // Esto no debería pasar si la validación fue exitosa, pero es una buena salvaguarda
            Log::error('Error crítico: No se pudo sincronizar el usuario después de una validación exitosa.', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => 'Ocurrió un error al sincronizar la cuenta de usuario.',
            ]);
        }

        // 3. El resto del código original permanece igual, pero usando la variable $user
        // que ahora contiene el usuario de la base de datos local.
        if (Features::enabled(Features::twoFactorAuthentication()) && $user->hasEnabledTwoFactorAuthentication()) {
            Log::info('Usuario requiere autenticación de dos factores.', ['email' => $user->email]);
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return to_route('two-factor.login');
        }

        Log::info('Autenticando y iniciando sesión para el usuario.', ['email' => $user->email]);
        Auth::login($user, $request->boolean('remember'));

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