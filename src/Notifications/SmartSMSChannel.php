<?php

namespace Admin\Notifications;

use Admin\Helpers\SmartSms;
use Illuminate\Notifications\Notification;

class SmartSMSChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->getMessage($notifiable);

        (new SmartSms())->sendSms($notifiable->phone, $message);
    }
}