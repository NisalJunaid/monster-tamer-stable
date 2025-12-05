<?php

namespace App\Domain\Dex;

use InvalidArgumentException;

class StatCalculator
{
    public const STATS = [
        'hp',
        'attack',
        'defense',
        'special_attack',
        'special_defense',
        'speed',
    ];

    private EffortValueValidator $effortValueValidator;

    private array $natureModifiers;

    public function __construct(?EffortValueValidator $effortValueValidator = null, ?array $natureModifiers = null)
    {
        $this->effortValueValidator = $effortValueValidator ?? new EffortValueValidator();
        $this->natureModifiers = $this->normalizeNatures($natureModifiers ?? config('dex.natures', []));
    }

    /**
     * Calculate all battle stats for a monster.
     *
     * @param  array{hp:int,attack:int,defense:int,special_attack:int,special_defense:int,speed:int}  $baseStats
     * @param  array{hp:int,attack:int,defense:int,special_attack:int,special_defense:int,speed:int}  $individualValues
     * @param  array{hp:int,attack:int,defense:int,special_attack:int,special_defense:int,speed:int}  $effortValues
     * @return array{hp:int,attack:int,defense:int,special_attack:int,special_defense:int,speed:int}
     */
    public function calculate(array $baseStats, array $individualValues, array $effortValues, int $level, string $nature): array
    {
        $this->effortValueValidator->validate($effortValues);

        $stats = [];

        $stats['hp'] = $this->calculateHp(
            $baseStats['hp'] ?? 0,
            $individualValues['hp'] ?? 0,
            $effortValues['hp'] ?? 0,
            $level
        );

        foreach (array_slice(self::STATS, 1) as $stat) {
            $stats[$stat] = $this->calculateNonHpStat(
                $baseStats[$stat] ?? 0,
                $individualValues[$stat] ?? 0,
                $effortValues[$stat] ?? 0,
                $level,
                $this->natureMultiplier($nature, $stat)
            );
        }

        return $stats;
    }

    public function calculateHp(int $base, int $individualValue, int $effortValue, int $level): int
    {
        $evComponent = (int) floor($effortValue / 4);

        return (int) floor(((2 * $base + $individualValue + $evComponent) * $level) / 100) + $level + 10;
    }

    public function calculateNonHpStat(int $base, int $individualValue, int $effortValue, int $level, float $natureMultiplier = 1.0): int
    {
        $evComponent = (int) floor($effortValue / 4);

        $stat = (int) floor(((2 * $base + $individualValue + $evComponent) * $level) / 100) + 5;

        return (int) floor($stat * $natureMultiplier);
    }

    private function natureMultiplier(string $nature, string $stat): float
    {
        $natureKey = strtolower($nature);
        $modifiers = $this->natureModifiers[$natureKey] ?? null;

        if (! $modifiers) {
            return 1.0;
        }

        if ($modifiers['up'] === $stat) {
            return 1.1;
        }

        if ($modifiers['down'] === $stat) {
            return 0.9;
        }

        return 1.0;
    }

    private function normalizeNatures(array $natures): array
    {
        $normalized = [];

        foreach ($natures as $name => $modifiers) {
            if (! isset($modifiers['up'], $modifiers['down'])) {
                throw new InvalidArgumentException('Nature modifiers must include up and down keys.');
            }

            $normalized[strtolower($name)] = [
                'up' => $modifiers['up'],
                'down' => $modifiers['down'],
            ];
        }

        return $normalized;
    }
}
