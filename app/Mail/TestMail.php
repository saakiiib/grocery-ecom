<?php

namespace App\Mail;

use App\Models\CompanyDetails;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        $brand = trim((string) (CompanyDetails::cached()->company_name ?? '')) ?: 'Alam Mini Market';

        return new Envelope(subject: "Test email — {$brand} mail settings work");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.test');
    }
}
