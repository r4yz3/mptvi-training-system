<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| TESDA competency-based training. A program (qualification) is made of Units of
| Competency (Basic / Common / Core). During training each trainee is rated per
| unit — Competent / Not Yet Competent — which forms the Achievement Chart. This
| replaces the earlier weighted-numeric grades table.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60)->nullable();     // TESDA UoC code, optional
            $table->string('title');
            $table->string('type', 20)->default('Core'); // Basic | Common | Core
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['program_id', 'type', 'sort']);
        });

        Schema::create('competency_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_unit_id')->constrained()->cascadeOnDelete();
            $table->string('result', 20); // Competent | Not Yet Competent
            $table->date('rated_at')->nullable();
            $table->foreignId('rated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->unique(['applicant_id', 'competency_unit_id']);
        });

        Schema::dropIfExists('grades');
    }

    public function down(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('scores')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::dropIfExists('competency_results');
        Schema::dropIfExists('competency_units');
    }
};
