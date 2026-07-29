<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If an account exists for that email address, a password reset link has been sent.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:12', 'max:64'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60))->save();

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => AuditAction::PASSWORD_CHANGED->value,
                    'model' => 'User',
                    'model_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been reset.');
        }

        return back()->with('status', 'If an account exists for that email address, a password reset link has been sent.');
    }
}
