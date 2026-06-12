<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per (applicant, requirement)
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('requirement_key'); // index into config('requirements')
            $table->string('status')->default('Pending');   // Pending | Submitted | Verified | Rejected
            $table->string('reject_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['applicant_id', 'requirement_key']);
        });

        // Uploaded files (multi-file + version history) — paths on the PRIVATE disk
        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('path');            // storage/app/private/...
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // DPA access/action audit trail
        Schema::create('document_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_file_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');          // upload | view | download | verify | reject | received | unreceived | delete
            $table->string('detail')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_audits');
        Schema::dropIfExists('document_files');
        Schema::dropIfExists('documents');
    }
};
