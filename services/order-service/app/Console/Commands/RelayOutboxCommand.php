<?php

namespace App\Console\Commands;

use App\Messaging\EventPublisher;
use App\Messaging\RabbitMQConnectionFactory;
use App\Models\OutboxEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('outbox:relay')]
#[Description('Publica eventos pendentes do outbox no RabbitMQ')]
class RelayOutboxCommand extends Command
{
    public function __construct(
        private readonly EventPublisher $eventPublisher,
    ) {
        parent::__construct();
    }
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Outbox relay iniciado...');

        while (true) {
            $events = OutboxEvent::query()
                ->whereNull('published_at')
                ->orderBy('created_at', 'asc')
                ->limit(10)
                ->get();

            if ($events->isEmpty()) {
                sleep(1);
                continue;
            }

            $hadFailure = false;

            foreach ($events as $event) {
                try {
                    $this->eventPublisher->publish($event->event_type, $event->payload);

                    $event->update([
                        'published_at' => now(),
                        'attempts'     => $event->attempts + 1,
                    ]);

                    $this->line("Publicado: {$event->id}");
                } catch (\Throwable $e) {
                    $event->increment('attempts');
                    $this->error("Falha {$event->id}: {$e->getMessage()}");
                }
            }

            sleep($hadFailure ? 5 : 1);
            // to-do criar uma coluna next_attempt_at para evitar flood de tentativas
        }
    }
}
