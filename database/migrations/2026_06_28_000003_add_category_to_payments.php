<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Payments become multi-purpose: a category (Training fee, Uniform, ID card,
| Assessment fee, …) plus an optional free-text description. Only the training
| fee counts toward the program-fee balance + pipeline; the rest are extra
| collections. Existing rows backfill to "Training fee" (legacy behavior).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('category')->default('Training fee')->after('amount');
            $table->string('description')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn(['category', 'description']));
    }
};
