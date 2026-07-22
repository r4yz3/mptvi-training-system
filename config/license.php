<?php

return [
    // Ed25519 public key (base64). Safe to commit — cannot be used to forge
    // a license, only to verify one signed with the matching private key.
    'public_key' => 'sLNYCwFusZirllWBI/iXxITdINF30kZEGnm+zp98KtQ=',

    // Where the signed license token is read from on this machine.
    'license_path' => storage_path('license.key'),
];
