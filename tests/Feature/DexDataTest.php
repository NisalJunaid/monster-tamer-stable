<?php

namespace Tests\Feature;

use App\Models\InstanceMove;
use App\Models\MonsterInstance;
use App\Models\MonsterSpecies;
use App\Models\MonsterSpeciesStage;
use App\Models\Move;
use App\Models\Type;
use App\Models\TypeEffectiveness;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DexDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_populate_core_dex_data(): void
    {
        $this->seed();

        $this->assertSame(12, Type::count());
        $this->assertSame(144, TypeEffectiveness::count());
        $this->assertGreaterThanOrEqual(5, MonsterSpecies::count());
        $this->assertSame(15, MonsterSpeciesStage::count());
        $this->assertGreaterThanOrEqual(15, Move::count());
    }

    public function test_can_create_monster_instance_with_moves_and_training_values(): void
    {
        $this->seed();

        $user = User::factory()->create();
        $species = MonsterSpecies::first();
        $stage = $species->stages()->where('stage_number', 1)->firstOrFail();
        $moves = Move::take(4)->get();

        $instance = MonsterInstance::create([
            'user_id' => $user->id,
            'species_id' => $species->id,
            'current_stage_id' => $stage->id,
            'nickname' => 'Starter',
            'level' => 5,
            'experience' => 1200,
            'nature' => 'Jolly',
            'iv_json' => [
                'hp' => 31,
                'attack' => 31,
                'defense' => 31,
                'sp_attack' => 31,
                'sp_defense' => 31,
                'speed' => 31,
            ],
            'ev_json' => [
                'hp' => 0,
                'attack' => 0,
                'defense' => 0,
                'sp_attack' => 0,
                'sp_defense' => 0,
                'speed' => 0,
            ],
        ]);

        foreach ($moves as $index => $move) {
            InstanceMove::create([
                'monster_instance_id' => $instance->id,
                'move_id' => $move->id,
                'slot' => $index + 1,
            ]);
        }

        $instance->refresh();

        $this->assertCount(4, $instance->moves);
        $this->assertSame(31, $instance->iv_json['hp']);
        $this->assertSame(0, $instance->ev_json['attack']);
    }
}
