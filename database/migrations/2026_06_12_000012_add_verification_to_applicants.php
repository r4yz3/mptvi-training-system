<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The applicants table carries the full TESDA LPF (60+ string columns). On
     * MySQL/utf8mb4 the inline row size (varchar counts N*4 bytes) approaches the
     * 65,535-byte limit, so free-text columns are moved to TEXT (stored off-page)
     * to make room for the verification fields and keep the table sustainable.
     */
    private array $toText = [
        'street', 'religion', 'ethnic_group', 'guardian_name', 'guardian_address', 'medical',
        'father_name', 'father_occupation', 'mother_name', 'mother_occupation', 'mother_maiden_name',
        'spouse_name', 'spouse_occupation', 'emergency_name', 'emergency_address',
        'classification_other', 'school_last_attended', 'employer_name', 'employer_position',
        'birthplace_city', 'birthplace_province', 'birthplace_region',
    ];

    public function up(): void
    {
        // Only TEXT-convert on engines that need it (MySQL); SQLite is dynamically typed.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('applicants', function (Blueprint $table) {
                foreach ($this->toText as $col) {
                    if (Schema::hasColumn('applicants', $col)) {
                        $table->text($col)->nullable()->change();
                    }
                }
            });
        }

        Schema::table('applicants', function (Blueprint $table) {
            $add = function ($col, $cb) use ($table) {
                if (! Schema::hasColumn('applicants', $col)) {
                    $cb($table);
                }
            };
            $add('date_accomplished', fn ($t) => $t->date('date_accomplished')->nullable());
            $add('date_received', fn ($t) => $t->date('date_received')->nullable());
            $add('interviewed_by', fn ($t) => $t->string('interviewed_by', 160)->nullable());
            $add('checked_by', fn ($t) => $t->string('checked_by', 160)->nullable());
            $add('approved_by', fn ($t) => $t->string('approved_by', 160)->nullable());
            $add('signature_path', fn ($t) => $t->string('signature_path', 191)->nullable());
            $add('thumbmark_path', fn ($t) => $t->string('thumbmark_path', 191)->nullable());
            $add('interviewer_signature_path', fn ($t) => $t->string('interviewer_signature_path', 191)->nullable());
            $add('checked_signature_path', fn ($t) => $t->string('checked_signature_path', 191)->nullable());
            $add('approved_signature_path', fn ($t) => $t->string('approved_signature_path', 191)->nullable());
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn([
                'date_accomplished', 'date_received', 'interviewed_by', 'checked_by', 'approved_by',
                'signature_path', 'thumbmark_path', 'interviewer_signature_path',
                'checked_signature_path', 'approved_signature_path',
            ]);
        });
    }
};
