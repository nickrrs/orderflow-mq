<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class OutboxEvent extends Model
{
    use HasUuids;

    protected $table = 'outbox_events';
    protected $fillable = ['id', 'event_type', 'event_version', 'aggregate_id', 'payload', 'published_at', 'attempts', 'occurred_at'];
    protected $casts = [
        'payload' => 'array',
        'event_version' => 'integer',
        'aggregate_id' => 'integer',
        'published_at' => 'datetime',
        'attempts' => 'integer',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
