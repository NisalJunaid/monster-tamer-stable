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

    protected function prepareForValidation(): void
    {
        if ($this->filled('slot')) {
            $this->merge(['slot' => (int) $this->input('slot')]);
        }

        if ($this->filled('monster_instance_id') && is_numeric($this->input('monster_instance_id'))) {
            $this->merge(['monster_instance_id' => (int) $this->input('monster_instance_id')]);
        }
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
