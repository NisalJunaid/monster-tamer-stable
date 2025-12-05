<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monster_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('monster_species')->cascadeOnDelete();
            $table->foreignId('current_stage_id')->constrained('monster_species_stages')->cascadeOnDelete();
            $table->string('nickname')->nullable();
            $table->unsignedInteger('level')->default(1);
            $table->unsignedBigInteger('experience')->default(0);
            $table->string('nature')->nullable();
            $table->json('iv_json')->nullable();
            $table->json('ev_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'species_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monster_instances');
    }
};
