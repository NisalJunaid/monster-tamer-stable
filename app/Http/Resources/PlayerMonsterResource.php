<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerMonsterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'species' => [
                'id' => $this->species?->id,
                'name' => $this->species?->name,
            ],
            'level' => $this->level,
            'current_hp' => $this->current_hp,
            'max_hp' => $this->max_hp,
            'is_in_team' => (bool) $this->is_in_team,
            'team_slot' => $this->team_slot,
            'nickname' => $this->nickname,
        ];
    }
}
