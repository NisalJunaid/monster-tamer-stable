<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ZoneSpawnGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'pool_mode' => ['required', 'in:any,type_based,rarity_based'],
            'types' => ['array'],
            'types.*' => ['integer', 'exists:types,id'],
            'rarity_tiers' => ['array'],
            'rarity_tiers.*' => ['string', 'max:50'],
            'num_species' => ['required', 'integer', 'between:1,50'],
            'level_min' => ['required', 'integer', 'min:1'],
            'level_max' => ['required', 'integer', 'min:1', 'gte:level_min'],
            'weight_min' => ['required', 'integer', 'min:1'],
            'weight_max' => ['required', 'integer', 'min:1', 'gte:weight_min'],
            'replace_existing' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rarityInput = $this->input('rarity_tiers', []);

        $rarityTiers = [];
        foreach ((array) $rarityInput as $value) {
            foreach (explode(',', (string) $value) as $piece) {
                $trimmed = trim($piece);
                if ($trimmed !== '') {
                    $rarityTiers[] = $trimmed;
                }
            }
        }

        $this->merge([
            'rarity_tiers' => $rarityTiers,
            'types' => array_filter((array) $this->input('types', [])),
        ]);
    }
}
