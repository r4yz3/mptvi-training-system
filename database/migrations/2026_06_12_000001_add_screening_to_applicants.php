<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->timestamp('screened_at')->nullable()->after('registered_at');
            $table->foreignId('screened_by')->nullable()->after('screened_at')->constrained('users')->nullOnDelete();
            $table->string('disqualify_reason')->nullable()->after('screened_by');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('screened_by');
            $table->dropColumn(['screened_at', 'disqualify_reason']);
        });
    }
};
