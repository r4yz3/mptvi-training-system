<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    /** Show the "enter your code" screen after a correct password. */
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login.2fa')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /** Verify a TOTP code (or a recovery code) and complete the login. */
    public function store(Request $request): RedirectResponse
    {
        $stash = $request->session()->get('login.2fa');
        if (! $stash) {
            return redirect()->route('login');
        }

        $user = User::find($stash['id']);
        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget('login.2fa');

            return redirect()->route('login');
        }

        $key = '2fa|' . $user->id . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'code' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ]);
        }

        $data = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $ok = false;
        if (! empty($data['code'])) {
            $ok = Totp::verify((string) $user->two_factor_secret, $data['code']);
        } elseif (! empty($data['recovery_code'])) {
            $ok = $this->consumeRecoveryCode($user, $data['recovery_code']);
        }

        if (! $ok) {
            RateLimiter::hit($key, 60);
            SecurityEvent::record('2fa_failed', $user);
            throw ValidationException::withMessages([
                'code' => 'The two-factor code was invalid. Try again.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->forget('login.2fa');
        Auth::login($user, (bool) ($stash['remember'] ?? false));
        $request->session()->regenerate();
        SecurityEvent::record('2fa_success', $user);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** Match + consume a one-time recovery code (each works once). */
    private function consumeRecoveryCode(User $user, string $input): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $norm = strtoupper(trim($input));
        $remaining = array_values(array_filter($codes, fn ($c) => strtoupper($c) !== $norm));

        if (count($remaining) === count($codes)) {
            return false; // no match
        }

        $user->two_factor_recovery_codes = $remaining;
        $user->save();

        return true;
    }
}
