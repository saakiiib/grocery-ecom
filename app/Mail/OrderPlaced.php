<?php

namespace App\Mail;

use App\Http\Controllers\Admin\OrderController;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order '.$this->order->number.' confirmed — Alam Mini Market',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.placed');
    }

    /** Invoice PDF travels with the confirmation (built lazily at send time). */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => Pdf::loadView('admin.orders.invoice', OrderController::invoiceData($this->order))->setPaper('a4')->output(),
                'invoice-'.$this->order->number.'.pdf',
                'application/pdf'
            ),
        ];
    }
}
