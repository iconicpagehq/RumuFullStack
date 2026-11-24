<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\BillingNotification;

class BillingNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $notification;
    public $subject;
    public $bodyHtml;

    /**
     * Create a new message instance.
     */
    public function __construct(BillingNotification $notification, string $subject, string $bodyHtml)
    {
        $this->notification = $notification;
        $this->subject = $subject;
        $this->bodyHtml = $bodyHtml;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.billing.notification',
            with: [
                'bodyHtml' => $this->bodyHtml
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
        $attachments = [];
        
        if ($this->notification->invoice_id) {
            $invoice = $this->notification->invoice;
            if ($invoice && $invoice->pdf_path) {
                // Assuming it's stored in public disk
                $path = storage_path('app/public/' . $invoice->pdf_path);
                if (file_exists($path)) {
                    $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromPath($path)
                            ->as('Invoice_' . $invoice->invoice_number . '.pdf')
                            ->withMime('application/pdf');
                }
            }
        }
        
        return $attachments;
    }
}
