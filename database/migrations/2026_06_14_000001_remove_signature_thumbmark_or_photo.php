<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | No more stored files for signatures, thumbmarks, or OR receipts (client
 | request, 2026-06-14). The LPF keeps the typed signatory names + dates and
 | is signed on the printed form; payments keep the OR number. This drops the
 | now-unused image-path columns.
 */
return new class extends Migration
{
    private array $applicantCols = [
        'signature_path', 'thumbmark_path',
        'interviewer_signature_path', 'checked_signature_path', 'approved_signature_path',
    ];

    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            foreach ($this->applicantCols as $col) {
                if (Schema::hasColumn('applicants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'or_photo_path')) {
                $table->dropColumn('or_photo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            foreach ($this->applicantCols as $col) {
                if (! Schema::hasColumn('applicants', $col)) {
                    $table->string($col, 191)->nullable();
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'or_photo_path')) {
                $table->string('or_photo_path')->nullable();
            }
        });
    }
};
