<?php

namespace App\Services;

use App\Models\Bag;
use App\Models\BagItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BagService
{
    public function getOrCreateBagForUser(int $userId): Bag
    {
        return Bag::firstOrCreate(['user_id' => $userId]);
    }

    public function getBagWithItems(int $userId): Bag
    {
        $bag = $this->getOrCreateBagForUser($userId);
        return Bag::query()
            ->with(['bagItems.item'])
            ->findOrFail($bag->id);
    }

    public function addItem(int $userId, int $itemId, int $qty): void
    {
        DB::transaction(function() use ($userId, $itemId, $qty) {
            $bag = $this->getOrCreateBagForUser($userId);

            $row = BagItem::firstOrCreate(['bag_id' => $bag->id, 'item_id' => $itemId]);
            $row->quantity = $row->quantity + $qty;
            $row->save();
        });
    }

    public function consumeItem(int $userId, int $itemId, int $qty): void
    {
        DB::transaction(function() use ($userId, $itemId, $qty) {
            $bag = $this->getOrCreateBagForUser($userId);

            $row = BagItem::where('bag_id', $bag->id)->where('item_id', $itemId)->lockForUpdate()->first();
            if (!$row || $row->quantity < $qty) {
                throw ValidationException::withMessages([
                    'item_id' => 'Not enough quantity.',
                ]);
            }

            $row->quantity -= $qty;
            $row->save();
        });
    }
}
