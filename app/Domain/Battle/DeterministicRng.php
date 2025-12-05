<?php

namespace App\Domain\Battle;

class DeterministicRng
{
    private int $state;

    public function __construct(private readonly int $seed, ?int $state = null)
    {
        $this->state = $state ?? $seed;
    }

    public function nextFloat(): float
    {
        $this->state = (int) (($this->state * 1664525 + 1013904223) % 4294967296);

        return $this->state / 4294967296;
    }

    public function currentState(): int
    {
        return $this->state;
    }
}
