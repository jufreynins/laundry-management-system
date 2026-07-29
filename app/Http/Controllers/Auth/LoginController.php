<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = strtolower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            AuditLog::create([
                'user_id' => $user?->id,
                'action' => AuditAction::LOGIN_FAILED->value,
                'model' => 'User',
                'model_id' => $user?->id ?? 0,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (!$user->active) {
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Contact your administrator.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::LOGIN->value,
            'model' => 'User',
            'model_id' => $user->id,
            'location_id' => $user->location_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
