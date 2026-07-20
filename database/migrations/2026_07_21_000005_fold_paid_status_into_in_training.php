<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Paying now enrols a trainee straight into training, so "Paid" is no longer
     * a distinct pipeline stage. Move any existing Paid trainees to In training.
     * (The Partial/Paid *payment* status is separate and unaffected.)
     */
    public function up(): void
    {
        DB::table('applicants')->where('status', 'Paid')->update(['status' => 'In training']);
    }

    public function down(): void
    {
        // Not reversible — the two states are merged going forward.
    }
};
