<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * School-Based vs Community-Based training offers.
     *  - school_based:    the normal TESDA training that runs for months and collects a fee.
     *  - community_based: free soft-skills training (no payment).
     * Existing programs default to school_based.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('training_type')->default('school_based')->after('qualification');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('training_type');
        });
    }
};
