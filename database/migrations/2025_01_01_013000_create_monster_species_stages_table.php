<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monster_species_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('species_id')->constrained('monster_species')->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_number');
            $table->string('name');
            $table->unsignedInteger('hp');
            $table->unsignedInteger('attack');
            $table->unsignedInteger('defense');
            $table->unsignedInteger('sp_attack');
            $table->unsignedInteger('sp_defense');
            $table->unsignedInteger('speed');
            $table->foreignId('evolves_to_stage_id')->nullable()->constrained('monster_species_stages')->nullOnDelete();
            $table->json('evolve_trigger_json')->nullable();
            $table->timestamps();

            $table->unique(['species_id', 'stage_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monster_species_stages');
    }
};
