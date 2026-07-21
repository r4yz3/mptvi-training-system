<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The pipeline stage "Qualified" (passed screening) is renamed to "Enrolled". */
    public function up(): void
    {
        DB::table('applicants')->where('status', 'Qualified')->update(['status' => 'Enrolled']);
    }

    public function down(): void
    {
        DB::table('applicants')->where('status', 'Enrolled')->update(['status' => 'Qualified']);
    }
};
