<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');               // whole pesos
            $table->string('type')->default('Partial');      // Full | Partial | Down | Reservation
            $table->string('method')->default('Cash');       // Cash | Check | GCash | Bank
            $table->string('or_number')->nullable();         // Official Receipt no.
            $table->date('paid_at');
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('or_photo_path')->nullable();     // private disk (audit)
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['applicant_id', 'voided_at']);
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
