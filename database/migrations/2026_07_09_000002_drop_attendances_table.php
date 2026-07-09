<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Attendance is no longer tracked — MPTVI runs a competency-based model, so a
| learner's progress is the Achievement Chart, not an attendance percentage.
| Moving a Paid learner into training is now a manual "Start training" action.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('attendances');
    }

    public function down(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status'); // Present | Absent | Late | Excused
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['applicant_id', 'date']);
        });
    }
};
