<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@mptvi.test')->firstOrFail();
    }

    /** Compute the current valid TOTP for a secret (mirrors an authenticator app). */
    private function codeFor(string $secret): string
    {
        $m = new \ReflectionMethod(Totp::class, 'codeAt');
        $m->setAccessible(true);

        return $m->invoke(null, $secret, intdiv(time(), 30));
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_failed_login_is_logged(): void
    {
        $this->post('/login', ['email' => 'admin@mptvi.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('security_events', ['type' => 'login_failed', 'email' => 'admin@mptvi.test']);
    }

    public function test_successful_login_is_logged(): void
    {
        $this->post('/login', ['email' => 'admin@mptvi.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(1, SecurityEvent::where('type', 'login_success')->count());
    }

    public function test_two_factor_enable_and_confirm_flow(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('two-factor.enable'))->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => $this->codeFor($user->two_factor_secret)])
            ->assertSessionHas('recoveryCodes');

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertCount(8, $user->two_factor_recovery_codes);
        $this->assertDatabaseHas('security_events', ['type' => '2fa_enabled', 'user_id' => $user->id]);
    }

    public function test_wrong_confirmation_code_does_not_enable(): void
    {
        $user = $this->admin();
        $this->actingAs($user)->post(route('two-factor.enable'));

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_login_with_two_factor_redirects_to_challenge(): void
    {
        $user = $this->admin();
        $user->forceFill([
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => Totp::recoveryCodes(),
        ])->save();

        $this->post('/login', ['email' => 'admin@mptvi.test', 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
    }

    public function test_two_factor_challenge_completes_login(): void
    {
        $secret = Totp::generateSecret();
        $user = $this->admin();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => Totp::recoveryCodes(),
        ])->save();

        // Password step → stashes pending 2FA login.
        $this->post('/login', ['email' => 'admin@mptvi.test', 'password' => 'password']);

        // Wrong code is rejected.
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        // Correct code completes the login.
        $this->post('/two-factor-challenge', ['code' => $this->codeFor($secret)])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('security_events', ['type' => '2fa_success', 'user_id' => $user->id]);
    }

    public function test_recovery_code_completes_login_and_is_consumed(): void
    {
        $codes = Totp::recoveryCodes();
        $user = $this->admin();
        $user->forceFill([
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $codes,
        ])->save();

        $this->post('/login', ['email' => 'admin@mptvi.test', 'password' => 'password']);
        $this->post('/two-factor-challenge', ['recovery_code' => $codes[0]])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        // Used code is removed; 7 remain.
        $this->assertCount(7, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_security_settings_page_is_admin_only(): void
    {
        $this->actingAs($this->admin())->get('/settings/security')->assertOk();

        $cashier = User::where('email', 'cashier@mptvi.test')->firstOrFail();
        $this->actingAs($cashier)->get('/settings/security')->assertForbidden();
    }
}
