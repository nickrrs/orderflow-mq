<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OutboxEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class OrderService
{
    public function store(array $validatedData): Order|null
    {
        return DB::transaction(function () use ($validatedData): Order|null {
            $totalAmount = 0;

            foreach ($validatedData['items'] as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }

            $order = Order::create([
                'customer_email' => $validatedData['customer_email'],
                'payment_method' => $validatedData['payment_method'],
                'total_amount' => $totalAmount,
                'total_items' => count($validatedData['items']),
            ]);

            $order->items()->createMany($validatedData['items']);
            $order->save();
            $order->refresh();

            $eventId = (string) Uuid::uuid4();
            OutboxEvent::create([
                'id' => $eventId,
                'event_type'    => 'order.created',
                'event_version' => 1,
                'aggregate_id'  => $order->id,
                'occurred_at'   => now()->toISOString(),
                'payload'       => [
                    'event_id'      => $eventId,
                    'event_type'    => 'order.created',
                    'event_version' => 1,
                    'occurred_at'   => now()->toISOString(),
                    'data' => [
                        'order_id'       => $order->id,
                        'customer_email' => $order->customer_email,
                        'total_amount'   => $order->total_amount,
                        'items'          => $order->items->toArray(),
                    ],
                ],
            ]);

            return $order;
        });
    }
}
