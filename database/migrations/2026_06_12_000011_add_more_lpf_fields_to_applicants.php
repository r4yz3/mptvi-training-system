<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Personal / education extras
            $table->string('ethnic_group')->nullable()->after('religion');
            $table->string('mother_maiden_name')->nullable()->after('mother_occupation');
            $table->string('school_last_attended')->nullable()->after('education');
            $table->string('year_graduated')->nullable()->after('school_last_attended');
            // Employment (if employed)
            $table->string('employer_name')->nullable()->after('emp_type');
            $table->string('employer_position')->nullable()->after('employer_name');
            // Government-issued IDs
            $table->string('sss_no')->nullable()->after('employer_position');
            $table->string('gsis_no')->nullable()->after('sss_no');
            $table->string('tin_no')->nullable()->after('gsis_no');
            $table->string('philhealth_no')->nullable()->after('tin_no');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn([
                'ethnic_group', 'mother_maiden_name', 'school_last_attended', 'year_graduated',
                'employer_name', 'employer_position', 'sss_no', 'gsis_no', 'tin_no', 'philhealth_no',
            ]);
        });
    }
};
