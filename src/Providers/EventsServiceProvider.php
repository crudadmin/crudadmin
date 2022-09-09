<?php

namespace Admin\Providers;

use Admin\Listeners\CheckDevEmailWhitelist;
use Admin\Listeners\JobProcessingListener;
use Admin\Listeners\OnAdminLoginListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;

class EventsServiceProvider extends ServiceProvider
{
    protected $listen = [
        JobProcessing::class => [
            JobProcessingListener::class,
        ],
        MessageSending::class => [
            CheckDevEmailWhitelist::class,
        ],
        Login::class => [
            OnAdminLoginListener::class,
        ],
    ];
}
