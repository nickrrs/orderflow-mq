<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['customer_email', 'status', 'total_amount', 'total_items', 'payment_method', 'payment_status'];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_items' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public $timestamps = true;

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }
}
