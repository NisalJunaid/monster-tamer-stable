<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battle_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battle_id')->constrained('battles')->cascadeOnDelete();
            $table->unsignedInteger('turn_number');
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('action_json');
            $table->json('result_json');
            $table->timestamps();

            $table->unique(['battle_id', 'turn_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battle_turns');
    }
};
