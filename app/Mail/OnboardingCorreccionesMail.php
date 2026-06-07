<?php

namespace App\Mail;

use App\Models\AgenciaOnboardingProyecto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingCorreccionesMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AgenciaOnboardingProyecto $proyecto, public string $emailDestino) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config("mail.from.address", "hola@bigstudio.cl"), "Agencia de Marketing Big Studio"),
            to: [new Address($this->emailDestino, $this->proyecto->cliente->nombre ?? "")],
            replyTo: [new Address("hola@bigstudio.cl", "BigStudio")],
            subject: "Necesitamos unos ajustes en tu onboarding · {$this->proyecto->titulo}",
        );
    }

    public function content(): Content
    {
        return new Content(view: "emails.onboarding.correcciones", with: [
            "proyecto" => $this->proyecto,
            "urlPublica" => $this->proyecto->urlPublica(),
            "comentarios" => $this->proyecto->comentarios()->where("autor","admin")->where("resuelto",false)->get(),
        ]);
    }
}
