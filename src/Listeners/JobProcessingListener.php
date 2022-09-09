<?php

namespace Admin\Listeners;
use Admin;

class JobProcessingListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //Admin Models in queues are not booted. Therefore we can experience wrong casting
        //because model is not properly configured. We need boot models before every queue.
        Admin::boot();
    }
}
