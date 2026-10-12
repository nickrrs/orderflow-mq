<x-mail::message>
# Pedido confirmado

Olá! Recebemos o pedido **#{{ $orderId }}**.

**Total:** R$ {{ number_format((float) $totalAmount, 2, ',', '.') }}

## Itens
@foreach ($items as $item)
- {{ $item['sku'] ?? 'item' }} — qtd {{ $item['quantity'] }} — R$ {{ number_format((float) ($item['price'] ?? 0), 2, ',', '.') }}
@endforeach

Obrigado pela compra!<br>
{{ config('app.name') }}
</x-mail::message>
