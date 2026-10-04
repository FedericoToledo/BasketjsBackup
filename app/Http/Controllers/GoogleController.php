<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect('/jugar.php?google=falta');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect('/jugar.php?google=error');
        }

        $email = $google->getEmail();
        if (! $email) {
            return redirect('/jugar.php?google=error');
        }

        $user = User::query()
            ->where(function ($consulta) use ($google, $email) {
                $consulta->where('google_id', $google->getId())
                    ->orWhere('email', $email);
            })
            ->first();

        if (! $user) {
            $user = new User;
            $user->password = Str::random(40);
        }

        $user->fill([
            'google_id' => $google->getId(),
            'name' => $google->getName() ?: 'Jugador',
            'email' => $email,
            'avatar' => $google->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?: now(),
        ]);

        try {
            $user->save();
        } catch (\Throwable) {
            return redirect('/jugar.php?google=error');
        }

        Auth::login($user, true);

        return redirect('/jugar.php?google=ok');
    }

    public function salir(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/jugar.php');
    }
}
