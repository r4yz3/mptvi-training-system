<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Major/Minor subject grading (college model) — replaces the TESDA competency
| rating. A program has Subjects, each Major or Minor with credit units. The
| registrar records a numeric grade (1.00 highest → 5.00 fail; 3.00 passing) per
| trainee per subject; the GWA is the unit-weighted average.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60)->nullable();
            $table->string('title');
            $table->string('category', 10)->default('Major'); // Major | Minor
            $table->unsignedTinyInteger('units')->default(1);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['program_id', 'category', 'sort']);
        });

        Schema::create('subject_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->decimal('grade', 3, 2); // 1.00 .. 5.00
            $table->date('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->unique(['applicant_id', 'subject_id']);
        });

        // Competency rating is retired in favour of Major/Minor grading.
        Schema::dropIfExists('competency_results');
        Schema::dropIfExists('competency_units');
    }

    public function down(): void
    {
        Schema::create('competency_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60)->nullable();
            $table->string('title');
            $table->string('type', 20)->default('Core');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
        Schema::create('competency_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_unit_id')->constrained()->cascadeOnDelete();
            $table->string('result', 20);
            $table->date('rated_at')->nullable();
            $table->foreignId('rated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->unique(['applicant_id', 'competency_unit_id']);
        });

        Schema::dropIfExists('subject_grades');
        Schema::dropIfExists('subjects');
    }
};
