<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Structured educational background: per level (Elementary … Post-Graduate),
| the school, year started, year graduated, and Graduate/Undergraduate/Ongoing
| status. Stored as JSON keyed by level.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->json('education_history')->nullable()->after('year_graduated');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', fn (Blueprint $t) => $t->dropColumn('education_history'));
    }
};
