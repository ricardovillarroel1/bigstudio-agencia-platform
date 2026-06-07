<?php

namespace App\Mail;

use App\Models\AgenciaOnboardingProyecto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingRecordatorioMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AgenciaOnboardingProyecto $proyecto,
        public string $emailDestino,
        public int $diasSinAvance
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'hola@bigstudio.cl'),
                "Agencia de Marketing Big Studio"
            ),
            to: [new Address($this->emailDestino, $this->proyecto->cliente->nombre ?? '')],
            replyTo: [new Address('hola@bigstudio.cl', 'BigStudio')],
            subject: "Recordatorio · Tu onboarding {$this->proyecto->titulo} te está esperando",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding.recordatorio',
            with: [
                'proyecto' => $this->proyecto,
                'urlPublica' => $this->proyecto->urlPublica(),
                'diasSinAvance' => $this->diasSinAvance,
            ],
        );
    }
}
