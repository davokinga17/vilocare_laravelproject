<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 10);

        return (new MailMessage)
            ->subject('ViloCare Password Reset')
            ->greeting('Dear ViloCare User,')
            ->line('We received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line("This password reset link will expire in {$expire} minutes.")
            ->line('If you did not request a password reset, no further action is required.')
            ->salutation("Regards,\nViloCare Team");
    }
}
