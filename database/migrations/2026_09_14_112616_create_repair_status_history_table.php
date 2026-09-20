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
        Schema::create('repair_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_ticket_id')->constrained('repair_tickets')->cascadeOnDelete();
            $table->enum('status', ['pending','in_progress','completed','cancelled']);
            $table->foreignId('changed_by')->constrained('users');
            $table->dateTime('changed_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_status_history');
    }
};
