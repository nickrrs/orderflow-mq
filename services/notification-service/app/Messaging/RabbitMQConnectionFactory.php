<?php

namespace App\Messaging;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQConnectionFactory
{
    public function connect(): AMQPStreamConnection
    {
        $config = config('rabbitmq');

        return new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost'],
        );
    }

    public function channel(?AMQPStreamConnection $connection = null): array
    {
        $connection ??= $this->connect();
        $channel = $connection->channel();

        return [$connection, $channel];
    }
}
