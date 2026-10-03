<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $toSlug) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->toSlug) {
            'packed' => 'Order '.$this->order->number.' is packed',
            'out_for_delivery' => 'Order '.$this->order->number.' is on its way',
            'cancelled' => 'Order '.$this->order->number.' cancelled',
            default => 'Order '.$this->order->number.' update',
        };

        return new Envelope(subject: $subject.' — Evergreen Foods');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.status');
    }
}
