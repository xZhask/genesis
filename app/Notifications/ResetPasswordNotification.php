<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** Enlace de recuperación de contraseña, en español y con el diseño de los correos del colegio. */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Restablece tu contraseña del portal')
            ->view('mail.reset-password', [
                'name' => $notifiable->name,
                'url' => $this->resetUrl($notifiable),
                'minutes' => $minutes,
            ]);
    }
}
