<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('monster_learned_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_monster_id')
                  ->constrained('player_monsters')
                  ->cascadeOnDelete();
            $table->foreignId('move_id')
                  ->constrained('moves')
                  ->cascadeOnDelete();
            $table->string('learned_method'); // item, tutor, etc
            $table->timestamps();

            $table->unique(['player_monster_id', 'move_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('monster_learned_moves');
    }
};
