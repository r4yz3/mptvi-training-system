<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Full Form Builder control:
|  - built-in fields can be moved to any category (section override),
|    reordered (position), and deleted (soft, non-locked only).
|  - custom fields gain an explicit position for unified ordering.
|  - categories (sections) can carry helper text (note).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_settings', function (Blueprint $table) {
            $table->string('section')->nullable()->after('key');   // category override
            $table->unsignedInteger('position')->nullable()->after('section'); // order within category
            $table->boolean('deleted')->default(false)->after('label'); // soft-removed built-in
        });

        Schema::table('custom_fields', function (Blueprint $table) {
            $table->unsignedInteger('position')->nullable()->after('sort_order');
        });

        Schema::table('form_sections', function (Blueprint $table) {
            $table->string('note')->nullable()->after('label'); // helper text under the category heading
        });
    }

    public function down(): void
    {
        Schema::table('field_settings', fn (Blueprint $t) => $t->dropColumn(['section', 'position', 'deleted']));
        Schema::table('custom_fields', fn (Blueprint $t) => $t->dropColumn('position'));
        Schema::table('form_sections', fn (Blueprint $t) => $t->dropColumn('note'));
    }
};
