<?php

namespace Admin\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckDevEmailWhitelist
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //We want allow this only in local dev mode and debug mode
        if ( app()->environment('local') === false || env('APP_DEBUG') === false ) {
            return;
        }

        $whitelist = array_filter(explode(',', env('MAIL_DEV_WHITELIST') ?: ''));

        //If is not local environment, skip this listener.
        if ( !is_array($whitelist) || count($whitelist) == 0 ) {
            return;
        }

        foreach ($event->message->getTo() as $email => $name) {
            if ( !in_array($email, $whitelist) ) {
                abort(500, 'Email dddress '.$email.' is not whitelisted in development mode.');
            }
        }
    }
}
