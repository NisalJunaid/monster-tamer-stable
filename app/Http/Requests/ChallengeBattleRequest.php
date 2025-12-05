<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChallengeBattleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opponent_user_id' => ['required', 'integer', 'exists:users,id'],
            'player_party' => ['required', 'array', 'min:1'],
            'player_party.*' => ['integer', 'exists:monster_instances,id'],
            'opponent_party' => ['required', 'array', 'min:1'],
            'opponent_party.*' => ['integer', 'exists:monster_instances,id'],
            'seed' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
