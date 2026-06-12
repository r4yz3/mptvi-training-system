<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-defined custom fields appended to the registration form.
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('key')->unique();              // slug used as the JSON key
            $table->string('type')->default('text');      // text|textarea|number|date|select|checkbox
            $table->json('options')->nullable();          // choices for select
            $table->string('section')->default('sec-additional'); // target form section id
            $table->boolean('required')->default(false);
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Standard form sections (seeded) — admin can show/hide & reorder.
        Schema::create('form_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Custom field values per applicant.
        Schema::table('applicants', function (Blueprint $table) {
            $table->json('custom_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applicants', fn (Blueprint $t) => $t->dropColumn('custom_data'));
        Schema::dropIfExists('form_sections');
        Schema::dropIfExists('custom_fields');
    }
};
