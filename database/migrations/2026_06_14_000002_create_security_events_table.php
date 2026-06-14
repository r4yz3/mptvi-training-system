<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Security audit log — every login success/failure, lockout, logout and
 | two-factor event, with IP + user agent. Reviewed by admins under
 | Settings → Security. Append-only (no app code updates rows).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();      // attempted email (may not match a user)
            $table->string('type', 32);               // login_success | login_failed | lockout | logout | 2fa_success | 2fa_failed | 2fa_enabled | 2fa_disabled
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['type', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
