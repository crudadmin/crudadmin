<?php

namespace Admin\Providers;

use Admin\Listeners\CheckDevEmailWhitelist;
use Admin\Listeners\OnAdminLoginListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;

class EventsServiceProvider extends ServiceProvider
{
    protected $listen = [
        MessageSending::class => [
            CheckDevEmailWhitelist::class,
        ],
        Login::class => [
            OnAdminLoginListener::class,
        ],
    ];
}
