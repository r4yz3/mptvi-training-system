<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ULI / Learner No. removed from the system (client request, 2026-07-06).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('applicants', 'uli')) {
            return;
        }
        // Drop the unique index first — SQLite can't drop an indexed column.
        Schema::table('applicants', fn (Blueprint $table) => $table->dropUnique(['uli']));
        Schema::table('applicants', fn (Blueprint $table) => $table->dropColumn('uli'));
    }

    public function down(): void
    {
        Schema::table('applicants', fn (Blueprint $table) => $table->string('uli')->nullable()->unique());
    }
};
