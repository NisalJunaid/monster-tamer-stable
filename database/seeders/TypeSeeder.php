<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Fire', 'Water', 'Nature', 'Electric', 'Ice', 'Earth', 'Wind', 'Metal', 'Toxic', 'Light', 'Shadow', 'Spirit',
        ];

        foreach ($types as $name) {
            Type::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
