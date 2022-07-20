<?php

namespace Admin\Notifications;

use Admin;
use Admin\Notifications\SmartSMSChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginVerificatorNotification extends Notification
{
    /**
     * The password reset token.
     *
     * @var string
     */
    private $user;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($user, $code)
    {
        $this->user = $user;

        $this->code = $code;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        if ( $notifiable->verification_method == 'email' ) {
            return ['mail'];
        } else if ( $notifiable->verification_method == 'sms' ) {
            return [SmartSMSChannel::class];
        }
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(_('Verifikačný kód'))
            ->line($this->getMessage())
            ->action($this->code, admin_action('Auth\VerificatorController@showVerificationForm').'?code='.$this->code);
    }

    public function getMessage()
    {
        return sprintf(_('Váš Verifikačný kód pre prihlásenie je: %s'), $this->code);
    }
}
