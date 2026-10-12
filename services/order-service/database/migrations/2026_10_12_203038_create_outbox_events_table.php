<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->uuid('id')->primary(); // = event_id da mensagem
            $table->string('event_type');   // order.created
            $table->unsignedSmallInteger('event_version')->default(1);
            $table->unsignedBigInteger('aggregate_id');
            $table->json('payload');        // envelope completo já montado
            $table->timestamp('occurred_at');
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();
            $table->index(['published_at', 'id']); // query do relay
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
