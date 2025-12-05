<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zone_spawn_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('monster_species')->cascadeOnDelete();
            $table->integer('weight');
            $table->integer('min_level');
            $table->integer('max_level');
            $table->string('rarity_tier');
            $table->json('conditions_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zone_spawn_entries');
    }
};
