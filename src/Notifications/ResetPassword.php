<?php

namespace Admin\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification
{
    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    private $user;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($token, $user)
    {
        $this->token = $token;

        $this->user = $user;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    protected function getTranslate($key)
    {
        if ( ($text = trans($key)) == $key )
            return false;

        return $text;
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if ( method_exists($this->user, 'getResetLink') )
            $action = $this->user->getResetLink($this->token);
        elseif ( $this->user instanceof \App\User )
            $action = action('\Admin\Controllers\Auth\ResetPasswordController@showResetForm', $this->token);
        else
            $action = route('password.reset', $this->token);

        return (new MailMessage)
            ->subject($this->getTranslate('passwords.email.subject') ?: 'Reset password')
            ->line($this->getTranslate('passwords.email.intro') ?: 'You are receiving this email because we received a password reset request for your account.')
            ->action($this->getTranslate('passwords.email.button') ?: 'Reset Password', $action)
            ->line($this->getTranslate('passwords.email.info') ?: 'If you did not request a password reset, no further action is required.');
    }
}
