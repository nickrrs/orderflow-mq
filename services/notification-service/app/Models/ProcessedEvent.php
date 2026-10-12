<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedEvent extends Model
{
    protected $primaryKey = 'event_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['event_id', 'event_type', 'processed_at'];

    protected $casts = [
        'event_id' => 'string',
        'event_type' => 'string',
        'processed_at' => 'datetime',
    ];
}
