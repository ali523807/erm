<?php

namespace App\Http\Controllers\Platform\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PlatformLoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (auth('platform')->check()) {
            return redirect()->route('platform.dashboard');
        }

        return view('platform.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('platform')->attempt(
            array_merge($credentials, ['is_active' => true]),
            $request->boolean('remember'),
        )) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        Auth::guard('platform')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('platform.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('platform')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }
}
