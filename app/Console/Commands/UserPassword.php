<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

/**
 * Reset a staff account's password from the terminal — for when an admin
 * forgets their login. Run on the server PC:
 *
 *   php artisan user:password                    (lists accounts, then prompts)
 *   php artisan user:password admin@example.com  (prompts for the new password)
 */
class UserPassword extends Command
{
    protected $signature = 'user:password {email? : The account email} {--password= : Set non-interactively (skips the prompt)} {--show : Echo the chosen password}';

    protected $description = "Reset a user's login password.";

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! $email) {
            $users = User::orderBy('name')->get(['name', 'email']);
            if ($users->isEmpty()) {
                $this->error('No user accounts exist.');

                return self::FAILURE;
            }
            $this->table(['Name', 'Email'], $users->map(fn ($u) => [$u->name, $u->email])->all());
            $email = $this->ask('Which account email?');
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("No account found with email: {$email}");

            return self::FAILURE;
        }

        if ($this->option('password') !== null) {
            $password = (string) $this->option('password');
        } else {
            $password = $this->secret('New password (min 8 chars; input hidden)');
            $confirm = $this->secret('Confirm new password');

            if ($password !== $confirm) {
                $this->error('Passwords do not match.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make(['password' => $password], ['password' => ['required', Password::defaults()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $err) {
                $this->error($err);
            }

            return self::FAILURE;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password updated for {$user->name} <{$user->email}>.");
        if ($this->option('show')) {
            $this->line("New password: {$password}");
        }

        return self::SUCCESS;
    }
}
