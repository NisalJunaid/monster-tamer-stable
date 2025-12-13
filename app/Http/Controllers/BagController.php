<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemUse;
use App\Models\PlayerMonster;
use App\Services\BagService;
use App\Services\ItemEffectService; // optional if you want method injection; safe to keep
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BagController extends Controller
{
    public function __construct(
        private readonly BagService $bagService,
        // IMPORTANT: do NOT inject ItemEffectService here, or /bag will 500 if the class isn't present.
    ) {}

    /**
     * GET /bag
     * Should ONLY read the bag and return items. No effect service needed.
     */
    public function index()
    {
        $userId = Auth::id();

        // If your BagService returns null when user has no bag, handle gracefully.
        $bag = $this->bagService->getBagWithItems($userId);

        if (!$bag) {
            return response()->json(['items' => []]);
        }

        $items = $bag->bagItems->map(fn ($bi) => [
            'item_id' => $bi->item_id,
            'key' => $bi->item->key ?? $bi->item->slug ?? null,
            'name' => $bi->item->name,
            'description' => $bi->item->description,
            'category' => $bi->item->category,
            'effect_type' => $bi->item->effect_type,
            'quantity' => $bi->quantity,
        ])->values();

        return response()->json(['items' => $items]);
    }

    /**
     * POST /bag/use
     */
    public function useItem(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'monster_id' => ['required', 'integer'],
            'replace_move_id' => ['nullable', 'integer'],
        ]);

        $userId = Auth::id();
        $bag = $this->bagService->getOrCreateBagForUser($userId);

        $item = Item::findOrFail($data['item_id']);

        // Ensure user owns monster (adjust columns to your schema)
        $monster = PlayerMonster::where('id', $data['monster_id'])
            ->where('user_id', $userId)
            ->firstOrFail();

        // Check quantity > 0
        $bagRow = $bag->bagItems()->where('item_id', $item->id)->first();
        if (!$bagRow || $bagRow->quantity < 1) {
            return response()->json([
                'result' => ['status' => 'failed', 'message' => 'You do not have this item.', 'changes' => []],
            ], 422);
        }

        /**
         * Resolve ItemEffectService ONLY here.
         * This prevents GET /bag from failing if the service isn't present/misnamed.
         */
        /** @var ItemEffectService $effects */
        $effects = app()->make(ItemEffectService::class);

        $result = $effects->apply($item, $monster, $data['replace_move_id'] ?? null);

        // consume on success
        if (($result['status'] ?? null) === 'success' && ($item->is_consumable ?? false)) {
            $this->bagService->consumeItem($userId, $item->id, 1);
            $bagRow->refresh();
        }

        ItemUse::create([
            'user_id' => $userId,
            'bag_id' => $bag->id,
            'item_id' => $item->id,
            'target_type' => 'player_monsters',
            'target_id' => $monster->id,
            'result' => $result['status'] ?? 'failed',
            'result_payload' => $result,
        ]);

        // Return minimal snapshot for UI update
        $monster->refresh();

        return response()->json([
            'result' => $result,
            'bag' => [
                'item_id' => $item->id,
                'quantity' => $bag->bagItems()->where('item_id', $item->id)->value('quantity') ?? 0,
            ],
            'monster' => [
                'id' => $monster->id,
                'current_hp' => (int) $monster->current_hp,
                'max_hp' => (int) $monster->max_hp,
                'equipped_moves' => method_exists($monster, 'moves')
                    ? $monster->moves()->select('moves.id', 'moves.name')->get()
                    : [],
            ],
        ]);
    }
}
