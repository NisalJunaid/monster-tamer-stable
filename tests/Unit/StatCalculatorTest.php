<?php

namespace Tests\Unit;

use App\Domain\Dex\EffortValueValidator;
use App\Domain\Dex\StatCalculator;
use InvalidArgumentException;
use Tests\TestCase;

class StatCalculatorTest extends TestCase
{
    private StatCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new StatCalculator(new EffortValueValidator());
    }

    public function test_calculates_stats_with_nature_modifiers(): void
    {
        $baseStats = [
            'hp' => 80,
            'attack' => 82,
            'defense' => 83,
            'special_attack' => 100,
            'special_defense' => 100,
            'speed' => 80,
        ];

        $ivs = array_fill_keys(StatCalculator::STATS, 31);

        $evs = [
            'hp' => 0,
            'attack' => 252,
            'defense' => 0,
            'special_attack' => 0,
            'special_defense' => 0,
            'speed' => 0,
        ];

        $stats = $this->calculator->calculate($baseStats, $ivs, $evs, 50, 'Adamant');

        $this->assertSame([
            'hp' => 155,
            'attack' => 147,
            'defense' => 103,
            'special_attack' => 108,
            'special_defense' => 120,
            'speed' => 100,
        ], $stats);
    }

    public function test_uses_neutral_modifiers_when_nature_is_unknown(): void
    {
        $stats = $this->calculator->calculate(
            array_fill_keys(StatCalculator::STATS, 50),
            array_fill_keys(StatCalculator::STATS, 0),
            array_fill_keys(StatCalculator::STATS, 0),
            10,
            'unknown'
        );

        $this->assertSame([
            'hp' => 75,
            'attack' => 15,
            'defense' => 15,
            'special_attack' => 15,
            'special_defense' => 15,
            'speed' => 15,
        ], $stats);
    }

    public function test_rejects_effort_values_exceeding_per_stat_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Effort values for attack must be between 0 and 252.');

        $evs = array_fill_keys(StatCalculator::STATS, 0);
        $evs['attack'] = 253;

        $this->calculator->calculate(
            array_fill_keys(StatCalculator::STATS, 1),
            array_fill_keys(StatCalculator::STATS, 1),
            $evs,
            50,
            'adamant'
        );
    }

    public function test_rejects_effort_values_exceeding_total_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total effort values exceed the cap of 510.');

        $evs = [
            'hp' => 252,
            'attack' => 252,
            'defense' => 4,
            'special_attack' => 4,
            'special_defense' => 4,
            'speed' => 4,
        ];

        $this->calculator->calculate(
            array_fill_keys(StatCalculator::STATS, 1),
            array_fill_keys(StatCalculator::STATS, 1),
            $evs,
            50,
            'adamant'
        );
    }
}
