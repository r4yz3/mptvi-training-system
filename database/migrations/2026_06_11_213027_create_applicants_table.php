<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();

            // Registration / pipeline (status & program stay here for P2–P4; an
            // enrollments table normalizes these at P5 when batches exist).
            $table->string('uli')->nullable()->unique();   // Unique Learner Identifier (MPT-YY-####)
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('Registered'); // pipeline stage
            $table->boolean('active')->default(true);
            $table->date('registered_at')->nullable();

            // Name
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('ext_name')->nullable();

            // Address
            $table->string('street')->nullable();
            $table->string('barangay');
            $table->string('district')->nullable();
            $table->string('city')->default('Magsaysay');
            $table->string('province')->default('Davao del Sur');
            $table->string('region')->default('Region XI (Davao Region)');

            // Contact
            $table->string('email')->nullable();
            $table->string('contact')->nullable();
            $table->string('nationality')->default('Filipino');
            $table->string('religion')->nullable();
            $table->boolean('voter')->default(false); // registered voter

            // Photo (2x2 ID picture) — public disk for now; DPA hardening in P4
            $table->string('photo_path')->nullable();

            // Personal
            $table->string('sex')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('emp_status')->nullable();
            $table->string('emp_type')->nullable();
            $table->date('birthdate')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('birthplace_city')->nullable();
            $table->string('birthplace_province')->nullable();
            $table->string('birthplace_region')->nullable();
            $table->string('education')->nullable();

            // Guardian
            $table->string('guardian_name')->nullable();
            $table->string('guardian_address')->nullable();

            // Health
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('eyesight')->nullable();
            $table->string('hearing')->nullable();
            $table->string('medical')->nullable();

            // Family background
            $table->string('father_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->string('family_rank')->nullable();
            $table->string('siblings')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('spouse_occupation')->nullable();
            $table->string('children')->nullable();

            // Course / scholarship / schedule
            $table->string('scholarship')->nullable();
            $table->string('class_session')->nullable();
            $table->string('school_year')->nullable();

            // Classification (TESDA client classification — multi-select) + disability
            $table->json('classifications')->nullable();
            $table->string('classification_other')->nullable();
            $table->string('disability_type')->nullable();
            $table->string('disability_cause')->nullable();

            // Emergency contact
            $table->string('emergency_name')->nullable();
            $table->string('emergency_relationship')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_address')->nullable();

            // Consent + notes
            $table->boolean('privacy_consent')->default(false);
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index(['status', 'active']);
            $table->index('barangay');
            $table->index('school_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
