<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-program, per-school-year amounts for the trackable extra fees
     * (School uniform, Assessment fee, …). The Miscellaneous fee stays on the
     * program; "Others" is ad-hoc and not scheduled. A trainee's expected extra
     * fees are the rows matching their program + school year.
     */
    public function up(): void
    {
        Schema::create('fee_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('school_year');
            $table->string('category');            // one of config('lpf.scheduled_fee_categories')
            $table->unsignedInteger('amount')->default(0); // whole pesos; 0 = not charged
            $table->timestamps();

            $table->unique(['program_id', 'school_year', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_items');
    }
};
