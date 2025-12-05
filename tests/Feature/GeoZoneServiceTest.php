<?php

namespace Tests\Feature;

use App\Domain\Geo\GeoZoneService;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeoZoneServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_point_matches_polygon_and_circle(): void
    {
        [$polygonZone, $circleZone] = $this->createZones();

        $service = new GeoZoneService();
        $result = $service->findZonesForPoint(37.05, -121.95);

        $this->assertSame([$circleZone->id, $polygonZone->id], $result->pluck('id')->all());
    }

    public function test_point_matches_circle_only_when_outside_polygon(): void
    {
        [, $circleZone] = $this->createZones();

        $service = new GeoZoneService();
        $result = $service->findZonesForPoint(37.05, -121.82);

        $this->assertSame([$circleZone->id], $result->pluck('id')->all());
    }

    private function createZones(): array
    {
        $polygonZone = Zone::create([
            'name' => 'Test Polygon Zone',
            'priority' => 10,
            'is_active' => true,
            'shape_type' => 'polygon',
            'geom' => 'POLYGON((-122.00 37.00, -122.00 37.10, -121.90 37.10, -121.90 37.00, -122.00 37.00))',
            'min_lat' => 37.00,
            'max_lat' => 37.10,
            'min_lng' => -122.00,
            'max_lng' => -121.90,
            'rules_json' => ['weather' => 'clear'],
        ]);

        $circleZone = Zone::create([
            'name' => 'Test Circle Zone',
            'priority' => 20,
            'is_active' => true,
            'shape_type' => 'circle',
            'center' => 'POINT(-121.95 37.05)',
            'radius_m' => 8000,
            'min_lat' => 36.95,
            'max_lat' => 37.15,
            'min_lng' => -122.05,
            'max_lng' => -121.85,
            'rules_json' => ['time' => 'day'],
        ]);

        return [$polygonZone, $circleZone];
    }
}
