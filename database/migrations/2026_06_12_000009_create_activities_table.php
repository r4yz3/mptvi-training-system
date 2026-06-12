<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();   // denormalized for display after user deletion
            $table->string('event');                    // created | updated | deleted
            $table->string('subject_type');             // model basename, e.g. Applicant
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');              // human-readable
            $table->timestamp('created_at')->nullable();
            $table->index(['subject_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
