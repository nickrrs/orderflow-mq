<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['event_id', 'order_id', 'customer_email', 'channel', 'status', 'body'];

    protected $casts = [
        'event_id' => 'string',
        'order_id' => 'integer',
        'customer_email' => 'string',
        'channel' => 'string',
        'status' => 'string',
        'body' => 'string',
    ];
}
