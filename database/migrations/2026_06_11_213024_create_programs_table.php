<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('qualification')->nullable(); // sector / qualification group
            $table->string('level')->nullable();          // e.g. NC II
            $table->unsignedInteger('hours')->default(0);  // TESDA training hours
            $table->unsignedInteger('fee')->default(0);    // miscellaneous fee (peso, whole)
            $table->unsignedInteger('slots')->default(0);  // default capacity
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
