<?php

namespace App\Models;

use App\Models\OrderItem;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $guarded = [];

    public function userAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
