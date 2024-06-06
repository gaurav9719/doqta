<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        //
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('doqta@app.com', 'Doqta App'),
            // replyTo: [
            //     new Address('taylor@example.com', 'Taylor Otwell'),
            // ],
            subject: 'Doqta Verification Email',
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
                "otp" => $this->data['otp'],
                "doqta" => $appImage,
                "message_data" => "Here is your email verification code for Doqta",
                "title" => "Email Verification",
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
