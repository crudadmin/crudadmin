<?php

namespace Admin\Providers;

use Admin\Listeners\CheckDevEmailWhitelist;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;

class EventsServiceProvider extends ServiceProvider
{
    protected $listen = [
        MessageSending::class => [
            CheckDevEmailWhitelist::class,
        ],
    ];
}
