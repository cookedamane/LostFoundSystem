<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate(['email' => 'required|email|max:150', 'password' => 'required|string|max:72']);

        // Staff use the web portal. Students (role=user) sign in through the mobile app.
        if (Auth::attempt($cred + ['is_active' => 1]) && in_array(Auth::user()->role, ['admin', 'officer', 'security', 'registrar', 'it_admin'], true)) {
            $request->session()->regenerate();
            ActivityLog::record('staff_login', Auth::user()->role);
            return redirect()->intended(route('dashboard'));
        }

        if (Auth::check()) {
            Auth::logout();
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'These details do not match an active staff account. Students sign in on the mobile app.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
