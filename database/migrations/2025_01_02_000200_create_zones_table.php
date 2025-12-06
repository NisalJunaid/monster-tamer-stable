<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('shape_type');
            $table->double('radius_m')->nullable();
            $table->double('min_lat')->nullable();
            $table->double('max_lat')->nullable();
            $table->double('min_lng')->nullable();
            $table->double('max_lng')->nullable();
            $table->json('rules_json')->nullable();
            $table->string('spawn_strategy')->default('manual');
            $table->json('spawn_rules')->nullable();
            $table->timestamps();

            if (! $this->usesPostgres()) {
                $table->text('geom')->nullable();
                $table->text('center')->nullable();
            }
        });

        if ($this->usesPostgres()) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
            DB::statement('ALTER TABLE zones ADD COLUMN geom geometry(Polygon, 4326) NULL');
            DB::statement('ALTER TABLE zones ADD COLUMN center geometry(Point, 4326) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }

    private function usesPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
