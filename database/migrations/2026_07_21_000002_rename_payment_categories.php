<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Collection list simplified to: Miscellaneous fee, School uniform,
     * Assessment fee, Others. Rename existing records so history stays
     * consistent — the balance/pipeline query filters on the exact
     * "Miscellaneous fee" string (formerly "Training fee").
     */
    public function up(): void
    {
        // Column default follows the renamed balance-driving category.
        Schema::table('payments', function (Blueprint $table) {
            $table->string('category')->default('Miscellaneous fee')->change();
        });

        DB::table('payments')->where('category', 'Training fee')->update(['category' => 'Miscellaneous fee']);
        DB::table('payments')->where('category', 'Uniform')->update(['category' => 'School uniform']);
        // Legacy extra collections fold into "Others" (their original label is
        // preserved in the description so the ledger still reads clearly).
        foreach (['ID card', 'Learning materials', 'Insurance', 'Other'] as $legacy) {
            DB::table('payments')->where('category', $legacy)->get()->each(function ($p) use ($legacy) {
                $desc = trim($legacy . ' — ' . ($p->description ?? ''), ' —');
                DB::table('payments')->where('id', $p->id)->update(['category' => 'Others', 'description' => $desc]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('category')->default('Training fee')->change();
        });

        DB::table('payments')->where('category', 'Miscellaneous fee')->update(['category' => 'Training fee']);
        DB::table('payments')->where('category', 'School uniform')->update(['category' => 'Uniform']);
        // "Others" cannot be reliably split back out; left as-is on rollback.
    }
};
