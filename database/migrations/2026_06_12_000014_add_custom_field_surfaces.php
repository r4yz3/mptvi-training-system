<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->boolean('show_in_list')->default(false);      // column in applicants table
            $table->boolean('filterable')->default(false);        // filter + search on list
            $table->boolean('show_on_dashboard')->default(false); // breakdown card (select/checkbox)
        });
    }

    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropColumn(['show_in_list', 'filterable', 'show_on_dashboard']);
        });
    }
};
