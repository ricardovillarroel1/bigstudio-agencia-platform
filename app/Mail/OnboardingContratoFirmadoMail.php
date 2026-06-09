<?php

namespace App\Mail;

use App\Models\AgenciaOnboardingProyecto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class OnboardingContratoFirmadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AgenciaOnboardingProyecto $proyecto,
        public string $emailDestino,
        public string $pdfBinario,
        public string $pdfNombre
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config("mail.from.address", "hola@bigstudio.cl"), "Agencia de Marketing Big Studio"),
            to: [new Address($this->emailDestino, $this->proyecto->cliente->nombre ?? "")],
            cc: [new Address("hola@bigstudio.cl", "BigStudio")],
            replyTo: [new Address("hola@bigstudio.cl", "BigStudio")],
            subject: "Copia de tu contrato firmado · {$this->proyecto->titulo}",
        );
    }

    public function content(): Content
    {
        return new Content(view: "emails.onboarding.contrato_firmado", with: [
            "proyecto" => $this->proyecto,
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinario, $this->pdfNombre)
                ->withMime("application/pdf"),
        ];
    }
}
