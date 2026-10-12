<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{order_id:int, customer_email:string, total_amount:mixed, items:array}  $orderData
     */
    public function __construct(
        public array $orderData,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pedido #{$this->orderData['order_id']} confirmado",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.order-created',
            with: [
                'orderId'       => $this->orderData['order_id'],
                'customerEmail' => $this->orderData['customer_email'],
                'totalAmount'   => $this->orderData['total_amount'],
                'items'         => $this->orderData['items'],
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
