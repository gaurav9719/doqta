<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;

class ChangeEmailRequest extends Mailable
{
    use Queueable, SerializesModels;
    protected $otp;
    /**
     * Create a new message instance.
     */
    public function __construct($otp)
    {
        //
        $this->otp = $otp;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('doqta@app.com', 'Doqta App'),
            subject: 'Change Email request',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $appImage = asset('public/app_icon/ai.png'); // Adjust the path as necessary

        return new Content(

            view: 'email.Common_Template',
            with: [
                'otp' => $this->otp,
                'doqta' => $appImage,
                'message' => "Here is your email verification code for Doqta",
                "title" => "Change email request verification",
                "body" => "Use the following code to verify your email address change:"
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
