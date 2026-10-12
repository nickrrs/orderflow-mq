<?php

return [
    'host'     => env('RABBITMQ_HOST', '127.0.0.1'),
    'port'     => (int) env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'app'),
    'password' => env('RABBITMQ_PASSWORD', 'secret'),
    'vhost'    => env('RABBITMQ_VHOST', '/'),
    'exchange' => env('RABBITMQ_EXCHANGE', 'orders'),
    'queue' => env('RABBITMQ_QUEUE', 'notification.order_created'),
    'routing_keys' => [
        'order_created' => env('RABBITMQ_ROUTING_KEY_ORDER_CREATED', 'order.created'),
    ],
];
