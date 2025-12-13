<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemUse extends Model
{
    protected $fillable = [
        'user_id','bag_id','item_id','target_type','target_id','result','result_payload'
    ];

    protected $casts = ['result_payload' => 'array'];

    public function user() { return $this->belongsTo(User::class); }
    public function bag() { return $this->belongsTo(Bag::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
