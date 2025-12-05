<?php

namespace App\Domain\Geo;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GeoZoneService
{
    public function findZonesForPoint(float $lat, float $lng): Collection
    {
        $query = Zone::query()
            ->where('is_active', true)
            ->where('min_lat', '<=', $lat)
            ->where('max_lat', '>=', $lat)
            ->where('min_lng', '<=', $lng)
            ->where('max_lng', '>=', $lng);

        if ($this->usesPostgis()) {
            $pointExpression = 'ST_SetSRID(ST_MakePoint(?, ?), 4326)';

            $query->where(function ($zones) use ($pointExpression, $lat, $lng) {
                $zones->where(function ($polygonZones) use ($pointExpression, $lat, $lng) {
                    $polygonZones
                        ->where('shape_type', 'polygon')
                        ->whereNotNull('geom')
                        ->whereRaw("ST_Contains(geom, {$pointExpression})", [$lng, $lat]);
                })->orWhere(function ($circleZones) use ($pointExpression, $lat, $lng) {
                    $circleZones
                        ->where('shape_type', 'circle')
                        ->whereNotNull('center')
                        ->whereNotNull('radius_m')
                        ->whereRaw(
                            "ST_DWithin(center::geography, {$pointExpression}::geography, radius_m)",
                            [$lng, $lat]
                        );
                });
            });

            return $query->orderByDesc('priority')->get();
        }

        $zones = $query->orderByDesc('priority')->get();

        return $zones
            ->filter(fn (Zone $zone) => $this->matchesPointWithoutPostgis($zone, $lat, $lng))
            ->values();
    }

    private function usesPostgis(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    private function matchesPointWithoutPostgis(Zone $zone, float $lat, float $lng): bool
    {
        if ($zone->shape_type === 'polygon' && $zone->geom) {
            $polygon = $this->parsePolygonWkt((string) $zone->geom);

            return $polygon !== [] && $this->pointInPolygon($lng, $lat, $polygon);
        }

        if ($zone->shape_type === 'circle' && $zone->center && $zone->radius_m) {
            $center = $this->parsePointWkt((string) $zone->center);

            return $center !== null && $this->haversineDistanceMeters($lat, $lng, $center['lat'], $center['lng']) <= $zone->radius_m;
        }

        return false;
    }

    /** @return array<int, array{lng: float, lat: float}> */
    private function parsePolygonWkt(string $wkt): array
    {
        if (! preg_match('/POLYGON\s*\(\(.*\)\)/i', $wkt)) {
            return [];
        }

        $coordinateSection = trim(strtoupper($wkt));
        $coordinateSection = preg_replace('/^POLYGON\s*\(\(/', '', $coordinateSection);
        $coordinateSection = preg_replace('/\)\)$/', '', (string) $coordinateSection);

        $pairs = array_filter(array_map('trim', explode(',', (string) $coordinateSection)));

        $points = [];

        foreach ($pairs as $pair) {
            [$lng, $lat] = array_map('floatval', preg_split('/\s+/', $pair) ?: []);
            $points[] = ['lng' => $lng, 'lat' => $lat];
        }

        return $points;
    }

    private function parsePointWkt(string $wkt): ?array
    {
        if (! preg_match('/POINT\s*\(([-0-9\.\s]+)\)/i', $wkt, $matches)) {
            return null;
        }

        [$lng, $lat] = array_map('floatval', preg_split('/\s+/', trim($matches[1])) ?: []);

        return ['lng' => $lng, 'lat' => $lat];
    }

    /**
     * Ray-casting algorithm for point-in-polygon.
     *
     * @param array<int, array{lng: float, lat: float}> $polygon
     */
    private function pointInPolygon(float $x, float $y, array $polygon): bool
    {
        $inside = false;
        $pointsCount = count($polygon);

        for ($i = 0, $j = $pointsCount - 1; $i < $pointsCount; $j = $i++) {
            $xi = $polygon[$i]['lng'];
            $yi = $polygon[$i]['lat'];
            $xj = $polygon[$j]['lng'];
            $yj = $polygon[$j]['lat'];

            $intersects = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private function haversineDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
