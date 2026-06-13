<?php

namespace App\Mail;

use App\Models\DocumentDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentDeliveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DocumentDelivery $delivery,
        public string $attachmentData,
        public string $attachmentName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->delivery->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-delivery',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => $this->attachmentData,
                $this->attachmentName
            )->withMime('application/pdf'),
        ];
    }
}
