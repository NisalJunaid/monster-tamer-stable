<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('items', function (Blueprint $table) {
      $table->id();
      $table->string('key')->unique();
      $table->string('name');
      $table->text('description')->nullable();
      $table->string('category')->default('misc');
      $table->boolean('is_consumable')->default(true);
      $table->unsignedInteger('stack_limit')->default(999);
      $table->string('effect_type');                 // heal_hp, teach_move, etc.
      $table->json('effect_payload')->nullable();    // {amount: 30} or {move_id: 12}
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('items');
  }
};
