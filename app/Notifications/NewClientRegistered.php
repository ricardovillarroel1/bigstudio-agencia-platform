<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class NewClientRegistered extends Notification
{
    use Queueable;

    protected $newUser;
    protected $clienteData;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $newUser, array $clienteData = [])
    {
        $this->newUser = $newUser;
        $this->clienteData = $clienteData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $razonSocial = $this->clienteData['razon_social'] ?? 'No especificada';
        $rut = $this->clienteData['rut'] ?? 'No especificado';
        $giro = $this->clienteData['giro'] ?? 'No especificado';
        $direccion = $this->clienteData['direccion'] ?? 'No especificada';

        return (new MailMessage)
            ->from(config('mail.from.address'), 'Big Studio - Integraciones')
            ->subject('Nuevo Cliente Registrado: ' . $this->newUser->name)
            ->greeting('Nuevo registro de cliente')
            ->line('Se ha registrado un nuevo cliente en el módulo de integraciones.')
            ->line('')
            ->line('**Datos del cliente:**')
            ->line('**Nombre:** ' . $this->newUser->name)
            ->line('**Email:** ' . $this->newUser->email)
            ->line('**Razón Social:** ' . $razonSocial)
            ->line('**RUT:** ' . $rut)
            ->line('**Giro:** ' . $giro)
            ->line('**Dirección:** ' . $direccion)
            ->line('**Fecha de registro:** ' . now()->format('d/m/Y H:i'))
            ->line('')
            ->action('Ver en el Panel de Administración', url('/admin/usuarios'))
            ->line('Recuerda hacer seguimiento a este nuevo cliente.')
            ->salutation('— Sistema de Integraciones Big Studio');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'new_user_id' => $this->newUser->id,
            'new_user_name' => $this->newUser->name,
            'new_user_email' => $this->newUser->email,
        ];
    }
}
