<?php

namespace App\Mail;

use App\Models\AgenciaTarea;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TareaCompartidaMail extends Mailable
{
    use Queueable, SerializesModels;

    public AgenciaTarea $tarea;
    public string $destinatario;

    public function __construct(AgenciaTarea $tarea, string $destinatario)
    {
        $this->tarea = $tarea;
        $this->destinatario = $destinatario;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address') ?: 'hola@bigstudio.cl', 'Agencia de Marketing Big Studio'),
            replyTo: [new Address('hola@bigstudio.cl', 'Big Studio')],
            to: [new Address($this->destinatario)],
            subject: 'Nueva tarea asignada: ' . $this->tarea->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agencia.tarea',
            with: [
                'tarea'    => $this->tarea,
                'cliente'  => $this->tarea->cliente,
                'panelUrl' => url('/agencia/mis-tareas'),
            ],
        );
    }
}
