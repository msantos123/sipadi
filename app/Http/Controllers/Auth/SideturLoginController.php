<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Sidetur\SideturUser;
use App\Providers\RouteServiceProvider;
use App\Services\SideturSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SideturLoginController extends Controller
{
    protected $sideturSyncService;

    public function __construct(SideturSyncService $sideturSyncService)
    {
        $this->sideturSyncService = $sideturSyncService;
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // 1. Validar credenciales contra Sidetur
        $sideturUser = SideturUser::where('email', $request->email)->first();

        if (! $sideturUser || ! Hash::check($request->password, $sideturUser->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // 2. Sincronizar el usuario con SIPADI
        $sipadiUser = $this->sideturSyncService->syncUser($request->email);

        if (! $sipadiUser) {
            // Si la sincronización falla por alguna razón después de una validación exitosa
            throw ValidationException::withMessages([
                'email' => ['No se pudo sincronizar la cuenta de usuario.'],
            ]);
        }

        // 3. Autenticar al usuario en SIPADI
        Auth::login($sipadiUser, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
