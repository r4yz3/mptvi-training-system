<?php
/**
 * Generates a signed license.key token for one deployment of the MPTVI
 * Training System. Run this on the DEVELOPER's machine only — it needs the
 * private signing key, which must never be copied to a deployed server.
 *
 * Usage:
 *   php generate-license.php <private-key-base64> <institution-name> <machine-uuid>
 *
 * Get the target machine's UUID by running, on that machine:
 *   powershell -Command "(Get-CimInstance Win32_ComputerSystemProduct).UUID"
 *
 * The output token goes into storage/license.key on the target deployment.
 */

if ($argc !== 4) {
    fwrite(STDERR, "Usage: php generate-license.php <private-key-base64> <institution-name> <machine-uuid>\n");
    exit(1);
}

[$_, $privateKeyB64, $institution, $machineId] = $argv;

$privateKey = base64_decode($privateKeyB64, true);

if ($privateKey === false || strlen($privateKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
    fwrite(STDERR, "Invalid private key.\n");
    exit(1);
}

$payload = json_encode([
    'institution' => $institution,
    'machine_id' => $machineId,
    'issued_at' => date('Y-m-d'),
]);

$signature = sodium_crypto_sign_detached($payload, $privateKey);

$b64url = fn (string $bin) => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');

$token = $b64url($payload) . '.' . $b64url($signature);

echo $token . "\n";
