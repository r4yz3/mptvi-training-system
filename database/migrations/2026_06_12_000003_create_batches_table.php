<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('code');                         // e.g. 2026-A
            $table->string('class_session')->default('Morning'); // Morning | Afternoon | Whole-day
            $table->string('class_days')->default('Mon–Fri'); // schedule pattern
            $table->string('school_year')->nullable();
            $table->unsignedInteger('capacity')->default(25);
            $table->string('trainer')->nullable();
            $table->string('venue')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();            // auto-computed from program hours
            $table->string('status')->default('Planned');    // Planned|Open|Ongoing|Closed|Completed
            $table->timestamps();
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('program_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });
        Schema::dropIfExists('batches');
    }
};
