<?php

namespace App\Messaging;

use PhpAmqpLib\Message\AMQPMessage;

class EventPublisher
{
    public function __construct(
        private readonly RabbitMQConnectionFactory $factory,
    ) {}

    public function publish(string $routingKey, array $payload): void
    {
        [$connection, $channel] = $this->factory->channel();

        try {
            $exchange = config('rabbitmq.exchange');

            // topic + durable; passive=false, auto_delete=false
            $channel->exchange_declare($exchange, 'topic', false, true, false);

            // confirma que o broker aceitou a mensagem
            $channel->confirm_select();

            $body = json_encode($payload, JSON_THROW_ON_ERROR);

            $message = new AMQPMessage($body, [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id'    => $payload['event_id'] ?? null,
                'timestamp'     => time(),
            ]);

            $channel->basic_publish($message, $exchange, $routingKey);
            $channel->wait_for_pending_acks();
        } finally {
            $channel->close();
            $connection->close();
        }
    }
}
