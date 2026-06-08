<?php

namespace App\Http\Controllers\CustomerPortal\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerPortalLoginController extends Controller
{
    public function create(): View
    {
        return view('customer-portal.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::guard('customer')->attempt([...$credentials, 'is_active' => true], $remember)) {
            return back()->withErrors(['email' => 'These portal credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user('customer')->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('customer-portal.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer-portal.login');
    }
}
