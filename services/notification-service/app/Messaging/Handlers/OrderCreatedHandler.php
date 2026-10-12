<?php

namespace App\Messaging\Handlers;

use App\Mail\OrderCreatedMail;
use App\Models\Notification;
use App\Models\ProcessedEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderCreatedHandler
{
    public function handle(array $event): void
    {
        $eventId = $event['event_id'];
        $data    = $event['data'];

        if (ProcessedEvent::where('event_id', $eventId)->exists()) {
            return;
        }

        $notification = Notification::firstOrCreate(
            ['event_id' => $eventId],
            [
                'order_id' => $data['order_id'],
                'customer_email' => $data['customer_email'],
                'channel' => 'email',
                'status' => 'pending',
                'body' => "Pedido #{$data['order_id']} confirmado",
            ]
        );

        if ($notification && $notification->status === 'sent') {
            ProcessedEvent::firstOrCreate(
               ['event_id' => $eventId],
               [
                'event_type' => $event['event_type'],
                'processed_at' => now(),
               ]
            );
            return;
        }

        Mail::to($data['customer_email'])->send(new OrderCreatedMail($data));

        DB::transaction(function () use ($notification, $event, $eventId) {
            $notification->update([
                'status' => 'sent',
            ]);

            ProcessedEvent::firstOrCreate(
                ['event_id' => $eventId],
                [
                    'event_type' => $event['event_type'],
                    'processed_at' => now(),
                ],
            );
        });
    }
}
