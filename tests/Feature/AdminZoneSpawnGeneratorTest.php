<?php

namespace Tests\Feature;

use App\Models\MonsterSpecies;
use App\Models\Type;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminZoneSpawnGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_manage_spawn_entries(): void
    {
        $zone = Zone::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.zones.spawns.index', $zone))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.zones.spawns.generate', $zone), [
                'pool_mode' => 'any',
                'num_species' => 3,
                'level_min' => 1,
                'level_max' => 2,
                'weight_min' => 10,
                'weight_max' => 20,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_generate_spawn_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $zone = Zone::factory()->create();
        $types = Type::factory()->count(2)->create();

        MonsterSpecies::factory()->count(12)->create([
            'primary_type_id' => $types->first()->id,
            'rarity_tier' => 'common',
        ]);

        $payload = [
            'pool_mode' => 'type_based',
            'types' => [$types->first()->id],
            'num_species' => 5,
            'level_min' => 2,
            'level_max' => 6,
            'weight_min' => 10,
            'weight_max' => 25,
            'replace_existing' => 1,
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.zones.spawns.generate', $zone), $payload);

        $response->assertRedirect(route('admin.zones.spawns.index', $zone));

        $this->assertDatabaseCount('zone_spawn_entries', 5);
        $this->assertDatabaseHas('zone_spawn_entries', [
            'zone_id' => $zone->id,
        ]);

        $entries = $zone->spawnEntries()->get();
        $this->assertCount(5, $entries);
        $this->assertEquals(1000, $entries->sum('weight'));
        $this->assertTrue($entries->every(fn ($entry) => $entry->min_level === 2 && $entry->max_level === 6));
    }
}
