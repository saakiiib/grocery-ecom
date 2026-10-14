<?php

namespace App\Mail;

use App\Models\CompanyDetails;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $url, public int $minutes) {}

    public function envelope(): Envelope
    {
        $brand = trim((string) (CompanyDetails::cached()->company_name ?? '')) ?: 'Alam Mini Market';

        return new Envelope(subject: "Reset your password — {$brand}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.auth.reset-password');
    }
}
