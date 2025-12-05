<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monster_species', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('primary_type_id')->constrained('types')->cascadeOnDelete();
            $table->foreignId('secondary_type_id')->nullable()->constrained('types')->nullOnDelete();
            $table->unsignedInteger('capture_rate')->default(45);
            $table->string('rarity_tier')->default('common');
            $table->unsignedInteger('base_experience')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monster_species');
    }
};
