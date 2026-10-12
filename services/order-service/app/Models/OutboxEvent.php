<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class OutboxEvent extends Model
{
    use HasUuids;

    protected $table = 'outbox_events';
    protected $fillable = ['id', 'event_type', 'event_version', 'aggregate_id', 'payload', 'published_at', 'attempts'];
    protected $casts = [
        'id' => 'uuid',
        'event_version' => 'integer',
        'aggregate_id' => 'integer',
        'published_at' => 'datetime',
        'attempts' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
