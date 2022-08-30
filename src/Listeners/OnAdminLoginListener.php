<?php

namespace Admin\Listeners;

use Admin;
use Admin\Eloquent\Concerns\HasAutoLogoutTrait;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OnAdminLoginListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //Check if admin is logging
        $userModel = Admin::getAuthModel();

        if ( !$event->user || Admin::isAdmin() === false || in_array(HasAutoLogoutTrait::class, class_uses_recursive($event->user), true) === false ){
            return;
        }

        if ( $date = $event->user->logout_date ) {
            $event->user->setLogoutTimestamp($date);
        }
    }
}
