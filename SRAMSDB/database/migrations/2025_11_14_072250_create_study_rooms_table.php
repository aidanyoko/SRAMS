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
        Schema::create('study_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('room_number')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('seat_capacity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_rooms');
    }
};
