# License tool

This app refuses to run unless a valid, machine-bound `storage/license.key`
is present. It's checked on every request by `App\Http\Middleware\VerifyLicense`,
which verifies an Ed25519 signature against the public key in `config/license.php`.

**Why:** protects against a future admin copying this codebase (including a
working `.env`/database) onto a different server and reselling/redeploying it
as their own product. Since the license is bound to the exact machine's
hardware UUID, copying the whole install to different hardware breaks the
license — and only the private key holder (the developer) can sign a new one.

## Issuing a license (developer only)

You need three things:
1. The private key — stored **outside this repo**, given to the developer
   separately when this was set up. Never commit it, never put it on a
   deployed server.
2. The exact institution name to embed (cosmetic — shown on the block page
   context, not itself a security boundary).
3. The target machine's hardware UUID:
   ```
   powershell -Command "(Get-CimInstance Win32_ComputerSystemProduct).UUID"
   ```

Then, on your own machine (with PHP + the `sodium` extension enabled):

```
php generate-license.php "<private-key-base64>" "Institution Name" "<machine-uuid>"
```

This prints a token. Save it as `storage/license.key` on the target
deployment (no trailing whitespace beyond a single newline is fine).

## Re-issuing (e.g. server hardware replaced)

Same process — get the new machine's UUID, generate a new token, replace
`storage/license.key` on that server.

## If the private key is ever lost

There's no recovery — generate a brand new keypair, update
`config/license.php`'s `public_key` in the app, and re-issue license.key for
every existing deployment.
