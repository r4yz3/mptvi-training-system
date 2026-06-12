<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('result');           // Competent | Not Yet Competent
            $table->date('assessed_at');
            $table->string('assessor')->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->string('cert_number')->nullable()->after('uli');
            $table->date('certified_at')->nullable()->after('cert_number');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['cert_number', 'certified_at']);
        });
        Schema::dropIfExists('assessments');
    }
};
