<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Trainee training status — set during/after training, independent of the
| enrolment pipeline and the app-wide `active` flag.
| Values: Active | Inactive | Completed | Incomplete (null = not set).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('trainee_status')->nullable()->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', fn (Blueprint $t) => $t->dropColumn('trainee_status'));
    }
};
