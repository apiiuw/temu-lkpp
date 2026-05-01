<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        if (Auth::guard('agent')->check()) {
            return redirect()->route('agent.dashboard');
        } elseif (Auth::guard('pimpinan')->check()) {
            return redirect()->route('pimpinan.dashboard');
        } elseif (Auth::guard('superadmin')->check()) {
            return redirect()->route('superadmin.dashboard');
        }

        return view('auth.login', ['title' => 'Login Internal']);
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('agent')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('agent.dashboard'));
        }

        if (Auth::guard('pimpinan')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('pimpinan.dashboard'));
        }

        if (Auth::guard('superadmin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('superadmin.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => 'Email atau password tidak sesuai dengan akun yang terdaftar.',
        ]);
    }

    public function destroy(Request $request)
    {
        $guard = 'agent';
        if (Auth::guard('pimpinan')->check()) {
            $guard = 'pimpinan';
        } elseif (Auth::guard('superadmin')->check()) {
            $guard = 'superadmin';
        }
        
        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
