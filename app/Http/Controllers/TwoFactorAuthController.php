<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Per-user opt-in two-factor setup, managed from the user's own Profile page.
 * Enable → scan QR → confirm a code → 2FA active + recovery codes shown once.
 */
class TwoFactorAuthController extends Controller
{
    /** Begin setup: generate an (unconfirmed) secret; the Profile page shows the QR. */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->two_factor_secret = Totp::generateSecret();
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return back();
    }

    /** Confirm the first code → activate 2FA and reveal recovery codes once. */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if (! $user->two_factor_secret) {
            return back()->withErrors(['code' => 'Start the setup first.']);
        }

        if (! Totp::verify((string) $user->two_factor_secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'That code is incorrect. Check your authenticator app and try again.']);
        }

        $codes = Totp::recoveryCodes();
        $user->two_factor_recovery_codes = $codes;
        $user->two_factor_confirmed_at = now();
        $user->save();
        SecurityEvent::record('2fa_enabled', $user);

        return back()->with('recoveryCodes', $codes);
    }

    /** Turn 2FA off. Requires the current password once it's confirmed. */
    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Cancelling an unconfirmed setup needs no password; disabling active 2FA does.
        if ($user->hasTwoFactorEnabled()) {
            $request->validate(['password' => ['required', 'current_password']]);
        }

        $confirmed = $user->hasTwoFactorEnabled();
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        if ($confirmed) {
            SecurityEvent::record('2fa_disabled', $user);
        }

        return back();
    }
}
