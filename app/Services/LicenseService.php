<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class LicenseService
{
    private ?array $result = null;

    /**
     * Verify the installed license token against this machine and the
     * embedded public key. Result is memoized per-request.
     *
     * @return array{valid: bool, reason: ?string, institution: ?string}
     */
    public function check(): array
    {
        if ($this->result !== null) {
            return $this->result;
        }

        $path = config('license.license_path');

        if (! is_file($path)) {
            return $this->result = ['valid' => false, 'reason' => 'missing', 'institution' => null];
        }

        $token = trim(file_get_contents($path));
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return $this->result = ['valid' => false, 'reason' => 'malformed', 'institution' => null];
        }

        [$payloadB64, $sigB64] = $parts;
        $payloadJson = base64_decode($this->padBase64Url($payloadB64), true);
        $signature = base64_decode($this->padBase64Url($sigB64), true);
        $publicKey = base64_decode(config('license.public_key'), true);

        if ($payloadJson === false || $signature === false || $publicKey === false) {
            return $this->result = ['valid' => false, 'reason' => 'malformed', 'institution' => null];
        }

        if (! sodium_crypto_sign_verify_detached($signature, $payloadJson, $publicKey)) {
            return $this->result = ['valid' => false, 'reason' => 'bad_signature', 'institution' => null];
        }

        $payload = json_decode($payloadJson, true);

        if (! is_array($payload) || empty($payload['institution']) || empty($payload['machine_id'])) {
            return $this->result = ['valid' => false, 'reason' => 'malformed', 'institution' => null];
        }

        if (! hash_equals($this->currentMachineId(), (string) $payload['machine_id'])) {
            return $this->result = ['valid' => false, 'reason' => 'wrong_machine', 'institution' => $payload['institution']];
        }

        return $this->result = ['valid' => true, 'reason' => null, 'institution' => $payload['institution']];
    }

    private function padBase64Url(string $value): string
    {
        $value = strtr($value, '-_', '+/');

        return str_pad($value, strlen($value) + (4 - strlen($value) % 4) % 4, '=');
    }

    public function currentMachineId(): string
    {
        return Cache::rememberForever('license.machine_id', function () {
            $uuid = trim((string) shell_exec(
                'powershell -NoProfile -Command "(Get-CimInstance Win32_ComputerSystemProduct).UUID"'
            ));

            return $uuid !== '' ? $uuid : 'unknown';
        });
    }
}
