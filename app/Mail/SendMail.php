<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $name;
    public $attachment;
    public $file_name;

    public function __construct($name, $attachment = null, $file_name)
    {
        $this->name = $name;
        $this->attachment = $attachment;
        $this->file_name = $file_name;
    }
    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */

    public function envelope()
    {
        return new Envelope(
            subject: 'Hai ' . $this->name . ' Berikut Ini Adalah Ticket Kamu',

        );
    }
    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */

    public function content()
    {
        return new Content(
            view: 'email.send',
            with: ['name' => $this->name],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        if ($this->attachment) {
            return [
                Attachment::fromStorageDisk('public', $this->attachment)
                    ->as($this->file_name ?? basename($this->attachment))
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
