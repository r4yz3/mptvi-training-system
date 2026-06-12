<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make the Form Builder's signature/verification section discoverable.
        if (Schema::hasTable('form_sections')) {
            DB::table('form_sections')
                ->where('key', 'sec-verify')
                ->where('label', 'Verification')
                ->update(['label' => 'Verification & Signatures']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('form_sections')) {
            DB::table('form_sections')
                ->where('key', 'sec-verify')
                ->where('label', 'Verification & Signatures')
                ->update(['label' => 'Verification']);
        }
    }
};
