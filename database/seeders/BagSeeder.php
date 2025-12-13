<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bag;
use App\Models\BagItem;
use App\Models\Item;
use App\Models\User;

class BagSeeder extends Seeder
{
    public function run(): void
    {
        $potion = Item::where('key','potion_small')->first();
        $tm = Item::where('key','tm_example')->first();

        User::query()->chunk(200, function($users) use ($potion, $tm) {
            foreach ($users as $user) {
                $bag = Bag::firstOrCreate(['user_id' => $user->id]);

                if ($potion) {
                    BagItem::updateOrCreate(
                        ['bag_id' => $bag->id, 'item_id' => $potion->id],
                        ['quantity' => 3]
                    );
                }
                if ($tm) {
                    BagItem::updateOrCreate(
                        ['bag_id' => $bag->id, 'item_id' => $tm->id],
                        ['quantity' => 1]
                    );
                }
            }
        });
    }
}
