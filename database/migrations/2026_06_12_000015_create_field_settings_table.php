<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();        // built-in field key
            $table->boolean('enabled')->default(true);
            $table->boolean('required')->default(false);
            $table->string('label')->nullable();     // label override
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_settings');
    }
};
