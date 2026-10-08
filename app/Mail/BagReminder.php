<?php

namespace App\Mail;

use App\Models\BagSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BagReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BagSnapshot $snapshot) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your bag is waiting — Evergreen Foods');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.bag-reminder');
    }
}
