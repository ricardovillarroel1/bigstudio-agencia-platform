<?php

namespace App\Mail;

use App\Models\AgenciaOnboardingProyecto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingInvitacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AgenciaOnboardingProyecto $proyecto,
        public string $emailDestino
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
            subject: "Tu onboarding BigStudio está listo · {$this->proyecto->titulo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding.invitacion',
            with: [
                'proyecto' => $this->proyecto,
                'urlPublica' => $this->proyecto->urlPublica(),
            ],
        );
    }
}
