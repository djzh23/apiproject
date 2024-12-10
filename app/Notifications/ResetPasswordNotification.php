<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('Passwort zurücksetzen')
            ->greeting('Hallo!')
            ->line('Sie erhalten diese E-Mail, weil wir eine Anfrage zum Zurücksetzen des Passworts für Ihr Konto erhalten haben.')
            ->action('Passwort zurücksetzen', $url)
            ->line('Dieser Link zum Zurücksetzen des Passworts läuft in 60 Minuten ab.')
            ->line('Wenn Sie keine Zurücksetzung des Passworts angefordert haben, ist keine weitere Aktion erforderlich.')
            ->salutation('Mit freundlichen Grüßen'. 'PPM-Project-Team');
    }
}
