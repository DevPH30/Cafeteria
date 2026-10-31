<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerResetPasswordNotification extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return url(route('customer.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefinição de senha - Café Aroma')
            ->greeting('Olá!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta de cliente.')
            ->action('Redefinir senha', $url)
            ->line('Este link expira em '.config('auth.passwords.customers.expire').' minutos.')
            ->line('Se você não solicitou a redefinição, ignore este e-mail.');
    }
}
