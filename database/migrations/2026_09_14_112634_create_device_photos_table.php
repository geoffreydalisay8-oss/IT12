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
        Schema::create('device_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_ticket_id')->constrained('repair_tickets')->cascadeOnDelete();
            $table->string('photo_path', 255);
            $table->enum('photo_type', [ 'intake', 'release'])->default('intake');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->dateTime('uploaded_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_photos');
    }
};
