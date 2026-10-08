<?php

namespace App\Mail;

use App\Models\ProductVariant;
use App\Models\StockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StockAlert $alert, public ProductVariant $variant, public float $price) {}

    public function envelope(): Envelope
    {
        $subject = $this->alert->type === 'price_drop'
            ? 'Price drop: '.$this->alert->product->name.' now £'.number_format($this->price, 2)
            : 'Back in stock: '.$this->alert->product->name;

        return new Envelope(subject: $subject.' — Alam Mini Market');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.stock-alert');
    }
}
