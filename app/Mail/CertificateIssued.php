<?php

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateIssued extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $certificate;
    public $user;
    public $course;

    /**
     * Create a new message instance.
     */
    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
        $this->user = $certificate->user;
        $this->course = $certificate->course;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تم إصدار شهادتك بنجاح - ' . $this->course->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.certificate-issued',
            with: [
                'userName' => $this->user->name,
                'courseTitle' => $this->course->title,
                'certificateId' => $this->certificate->serial_number,
                'verificationUrl' => $this->certificate->verification_url,
                'issuedAt' => $this->certificate->issued_at->format('Y-m-d'),
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
        if ($this->certificate->file_path && file_exists(public_path($this->certificate->file_path))) {
            return [
                Attachment::fromPath(public_path($this->certificate->file_path))
                    ->as('certificate.pdf')
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}