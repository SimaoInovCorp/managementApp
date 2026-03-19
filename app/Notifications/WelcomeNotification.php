<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a new user when their account is created by an administrator.
 * Includes a temporary password and a link to the login page.
 */
class WelcomeNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $temporaryPassword  Plain-text temporary password to include in the email.
     */
    public function __construct(
        public readonly string $temporaryPassword,
    ) {}

    /** @return string[] */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $loginUrl = url('/login');

        return (new MailMessage)
            ->subject("Welcome to {$appName} — Your account is ready")
            ->greeting("Hello {$notifiable->name},")
            ->line("An account has been created for you in **{$appName}**.")
            ->line('Here are your login credentials:')
            ->line("**Email:** {$notifiable->email}")
            ->line("**Temporary password:** `{$this->temporaryPassword}`")
            ->action('Log in now', $loginUrl)
            ->line('Please change your password after your first login.')
            ->line('If you have any questions, contact your administrator.');
    }
}
