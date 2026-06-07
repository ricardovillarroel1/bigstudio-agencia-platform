<?php

namespace App\Mail;

use App\Models\AgenciaOnboardingProyecto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingCompletadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AgenciaOnboardingProyecto $proyecto)
    {
    }

    public function envelope(): Envelope
    {
        $clienteNombre = $this->proyecto->cliente->nombre ?? 'Cliente';

        return new Envelope(
            from: new Address(
                config('mail.from.address', 'hola@bigstudio.cl'),
                config('mail.from.name', 'BigStudio')
            ),
            to: [new Address('hola@bigstudio.cl', 'BigStudio')],
            subject: "🎉 Onboarding completado — {$clienteNombre}",
        );
    }

    public function content(): Content
    {
        $secciones = $this->proyecto->plantilla->secciones ?? [];
        $respuestas = $this->proyecto->respuestas()->get()
            ->groupBy('seccion_key')
            ->map(fn($grupo) => $grupo->pluck('valor', 'campo_key')->toArray())
            ->toArray();
        $archivos = $this->proyecto->archivos()->get()
            ->groupBy('seccion_key')
            ->toArray();

        return new Content(
            view: 'emails.onboarding.completado',
            with: [
                'proyecto' => $this->proyecto,
                'secciones' => $secciones,
                'respuestas' => $respuestas,
                'archivos' => $archivos,
                'adminUrl' => url('/agencia/onboardings/' . $this->proyecto->id),
            ],
        );
    }
}
