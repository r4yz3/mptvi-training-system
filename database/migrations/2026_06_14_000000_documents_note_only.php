<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Documentary requirements become NOTE-ONLY (client request, 2026-06-14):
 | staff just type a note + pick a simple status per requirement — no file or
 | photo upload — because some applicants can't provide the exact documents.
 | This drops the file-upload + DPA audit machinery and adds a free-text note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->text('note')->nullable()->after('status');
            $table->foreignId('noted_by')->nullable()->after('note')->constrained('users')->nullOnDelete();
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['reject_reason', 'verified_at']);
        });

        // The file-upload + audit tables are no longer used.
        Schema::dropIfExists('document_audits');
        Schema::dropIfExists('document_files');
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('noted_by');
            $table->dropColumn('note');
            $table->string('reject_reason')->nullable()->after('status');
            $table->foreignId('verified_by')->nullable()->after('reject_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });

        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('document_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_file_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('detail')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['document_id', 'created_at']);
        });
    }
};
