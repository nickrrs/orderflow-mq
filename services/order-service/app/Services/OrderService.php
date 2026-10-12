<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function store(array $validatedData): Order|null
    {
        return DB::transaction(function () use ($validatedData): Order|null {
            $totalAmount = 0;

            foreach ($validatedData['items'] as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }
            $validatedData['total_amount'] = $totalAmount;
            $validatedData['total_items'] = count($validatedData['items']);
            $order = Order::create($validatedData);
            $order->items()->createMany($validatedData['items']);
            $order->save();
            $order->refresh();
            return $order;
        });
    }
}
