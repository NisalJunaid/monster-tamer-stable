<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moves', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('type_id')->constrained('types')->cascadeOnDelete();
            $table->enum('category', ['physical', 'special', 'status']);
            $table->unsignedInteger('power')->nullable();
            $table->unsignedTinyInteger('accuracy')->nullable();
            $table->unsignedSmallInteger('pp');
            $table->integer('priority')->default(0);
            $table->json('effect_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moves');
    }
};
