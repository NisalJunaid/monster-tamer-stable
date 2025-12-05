<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BattleActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['move', 'swap'])],
            'slot' => ['required_if:type,move', 'integer', 'min:1', 'max:4'],
            'monster_instance_id' => ['required_if:type,swap', 'integer', 'exists:monster_instances,id'],
        ];
    }
}
