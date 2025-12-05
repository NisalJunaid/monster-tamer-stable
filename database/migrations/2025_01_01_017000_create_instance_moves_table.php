<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instance_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monster_instance_id')->constrained('monster_instances')->cascadeOnDelete();
            $table->foreignId('move_id')->constrained('moves')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot');
            $table->timestamps();

            $table->unique(['monster_instance_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instance_moves');
    }
};
