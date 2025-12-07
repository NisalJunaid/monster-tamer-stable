<?php

namespace App\Events;

use App\Http\Resources\PlayerMonsterResource;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class UserMonstersUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $user, public Collection $monsters)
    {
        $this->monsters->loadMissing('species');
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('users.'.$this->user->id);
    }

    public function broadcastAs(): string
    {
        return 'UserMonstersUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'monsters' => PlayerMonsterResource::collection($this->monsters)->resolve(),
        ];
    }
}
