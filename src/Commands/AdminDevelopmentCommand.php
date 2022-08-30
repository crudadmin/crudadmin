<?php

namespace Admin\Commands;

use Illuminate\Console\Command;

class AdminDevelopmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:dev {type? : up/down or on/off}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Turn on/off development mode of administration.';

    /**
     * Cache dev mode value
     *
     * @var  string
     */
    private $cacheKey = 'adminDevMode';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check if admin dev mode is turned on
     *
     * @return  bool
     */
    public function hasDevMode()
    {
        return cache()->get($this->cacheKey) == true;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $type = $this->argument('type');

        if ( in_array($type, ['up', 'on', '1'], true) ) {
            $this->turnOn();
        } else if ( in_array($type, ['down', 'off', '0'], true) ) {
            $this->turnOff();
        } else {
            $this->toggleState();
        }
    }

    public function turnOn()
    {
        cache()->put($this->cacheKey, true);

        $this->comment('Development mode turned ON.');
    }

    public function turnOff()
    {
        $this->comment('Development mode turned OFF.');
        cache()->put($this->cacheKey, false);
    }

    public function toggleState()
    {
        //Turn on admin dev mode
        if ( $this->hasDevMode() == false ) {
            $this->turnOn();
        }

        //Turn of admin dev mode
        else {
            $this->turnOff();
        }
    }
}
