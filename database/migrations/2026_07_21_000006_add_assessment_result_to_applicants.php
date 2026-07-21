<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assessment is now a simple manual result on the trainee's profile
     * (Competent / Not Yet Competent), set by admin/registrar. Certificates are
     * gone. This field is independent of the pipeline status. Existing Certified
     * trainees are backfilled to "Competent".
     */
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('assessment_result')->nullable()->after('status');
        });

        DB::table('applicants')->where('status', 'Certified')->update(['assessment_result' => 'Competent']);
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn('assessment_result');
        });
    }
};
