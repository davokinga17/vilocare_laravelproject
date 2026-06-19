<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Patient $patient,
        public readonly Appointment $appointment,
        public readonly bool $manual = false
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->manual ? 'Manual appointment reminder sent' : 'New appointment reminder created',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment_reminder',
        );
    }
}
