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
        Schema::create('repair_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices');
            $table->foreignId('assigned_to') ->nullable() ->constrained('users');
            $table->text('problem_description');
            $table->decimal('quotation_price', 10,);
            $table->decimal('final_price', 10, 2);
            $table->enum('status', ['pending','in_progress','completed','cancelled'])->default('pending');
            $table->dateTime('date_received');
            $table->dateTime('date_completed')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_tickets');
    }
};
