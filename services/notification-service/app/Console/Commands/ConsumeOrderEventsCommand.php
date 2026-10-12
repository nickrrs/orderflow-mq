<?php

namespace App\Console\Commands;

use App\Messaging\Handlers\OrderCreatedHandler;
use App\Messaging\RabbitMQConnectionFactory;
use App\Messaging\Topology;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

#[Signature('notifications:consume')]
#[Description('Consome order.created e envia e-mail')]
class ConsumeOrderEventsCommand extends Command
{
    public function __construct(
        private readonly RabbitMQConnectionFactory $factory,
        private readonly Topology $topology,
        private readonly OrderCreatedHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int {
        $handler = $this->handler;
        [$connection, $channel] = $this->factory->channel();

        $this->topology->declare($channel);

        $queue = config('rabbitmq.queue');

        $channel->basic_qos(null, 1, null);

        $this->info("Escutando fila {$queue}...");

        $channel->basic_consume(
            $queue,
            consumer_tag: '',
            no_local: false,
            no_ack: false,
            exclusive: false,
            nowait: false,
            callback: function (AMQPMessage $message) use ($handler) {
                try {
                    $event = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);

                    if (($event['event_type'] ?? null) !== 'order.created') {
                        $this->warn('Evento ignorado: '.($event['event_type'] ?? '?'));
                        $message->ack();
                        return;
                    }

                    $handler->handle($event);
                    $message->ack();
                    $this->line("Processado: {$event['event_id']}");
                } catch (\Throwable $e) {
                    $this->error($e->getMessage());
                    $message->nack(requeue: false);
                }
            },
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return self::SUCCESS;
    }
}
