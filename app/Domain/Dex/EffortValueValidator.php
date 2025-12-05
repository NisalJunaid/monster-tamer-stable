<?php

namespace App\Domain\Dex;

use InvalidArgumentException;

class EffortValueValidator
{
    public const MAX_PER_STAT = 252;
    public const MAX_TOTAL = 510;

    /**
     * @param  array<string,int>  $effortValues
     */
    public function validate(array $effortValues): void
    {
        $this->assertAllowedStats($effortValues);
        $this->assertPerStatLimit($effortValues);
        $this->assertTotalLimit($effortValues);
    }

    /**
     * @param  array<string,int>  $effortValues
     */
    private function assertAllowedStats(array $effortValues): void
    {
        $unexpectedStats = array_diff(array_keys($effortValues), StatCalculator::STATS);

        if (! empty($unexpectedStats)) {
            throw new InvalidArgumentException('Unexpected EV stats: '.implode(', ', $unexpectedStats));
        }
    }

    /**
     * @param  array<string,int>  $effortValues
     */
    private function assertPerStatLimit(array $effortValues): void
    {
        foreach (StatCalculator::STATS as $stat) {
            $ev = $effortValues[$stat] ?? 0;

            if ($ev < 0 || $ev > self::MAX_PER_STAT) {
                throw new InvalidArgumentException("Effort values for {$stat} must be between 0 and ".self::MAX_PER_STAT.'.');
            }
        }
    }

    /**
     * @param  array<string,int>  $effortValues
     */
    private function assertTotalLimit(array $effortValues): void
    {
        $total = array_sum(array_intersect_key($effortValues, array_flip(StatCalculator::STATS)));

        if ($total > self::MAX_TOTAL) {
            throw new InvalidArgumentException('Total effort values exceed the cap of '.self::MAX_TOTAL.'.');
        }
    }
}
