<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BagItem extends Model
{
    protected $fillable = ['bag_id','item_id','quantity'];

    public function bag() { return $this->belongsTo(Bag::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
