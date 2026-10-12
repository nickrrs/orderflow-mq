<?php

namespace App\Messaging;

use PhpAmqpLib\Channel\AMQPChannel;

class Topology
{
    public function declare(AMQPChannel $channel): void
    {
        $exchange   = config('rabbitmq.exchange');
        $queue      = config('rabbitmq.queue');
        $routingKey = config('rabbitmq.routing_keys.order_created');

        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, $routingKey);
    }
}
