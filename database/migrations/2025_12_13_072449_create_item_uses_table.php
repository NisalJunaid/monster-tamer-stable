<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('item_uses', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('bag_id')->constrained('bags')->cascadeOnDelete();
      $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
      $table->string('target_type');          // e.g. 'player_monsters'
      $table->unsignedBigInteger('target_id');
      $table->string('result');               // success|failed|no_effect
      $table->json('result_payload')->nullable();
      $table->timestamps();

      $table->index(['target_type', 'target_id']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('item_uses');
  }
};
