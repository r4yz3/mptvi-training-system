<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Report/CSV exports are admin-approved. A staff member files a request (report
 | type + filters); an admin approves or rejects it; once approved the requester
 | can download the file (generated fresh from live data). Gives a full paper
 | trail of every PII export (Data Privacy Act).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // requester
            $table->string('type', 40);                 // key into App\Support\ReportCatalog
            $table->json('params')->nullable();         // the report filters
            $table->string('status', 16)->default('pending'); // pending|approved|rejected|downloaded
            $table->string('reason')->nullable();       // rejection reason
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // approved links expire
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_requests');
    }
};
