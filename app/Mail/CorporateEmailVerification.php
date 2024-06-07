<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class CorporateEmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    protected $otp;
    public function __construct($opt)
    {
        $this->otp= $opt;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@doqta.co', 'Doqta App'),
            subject: 'Corporate Email Verification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $appImage   =   asset('storage/app_icon/ai.png'); // Adjust the path as necessary
        
        return new Content(

            view: 'email.Common_Template',
            with: [
                "otp" => $this->otp,
                "doqta" => $appImage,
                "message_data" => "Here is your email verification code for Doqta",
                "title" => "Corporate Email Verification",
                "body" => "Use the following code to verify your email address:"
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
