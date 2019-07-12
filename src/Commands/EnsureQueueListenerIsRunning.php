<?php

namespace Admin\Commands;

use Illuminate\Console\Command;

/*
 * Thanks to
 * https://gist.github.com/ivanvermeyen/b72061c5d70c61e86875
 */
class EnsureQueueListenerIsRunning extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure that the queue listener is running. Add this command into crontab.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->isQueueListenerRunning()) {
            $this->comment('Queue listener is being started.');

            $pid = $this->startQueueListener();

            $this->saveQueueListenerPID($pid);
        }

        $this->comment('Queue listener is running.');
    }

    /**
     * Check if the queue listener is running.
     *
     * @return bool
     */
    private function isQueueListenerRunning()
    {
        if (! $pid = $this->getLastQueueListenerPID()) {
            return false;
        }

        $process = exec("ps -p $pid -opid=,cmd=");

        $processIsQueueListener = ! empty($process); // 5.6 - see comments
        return $processIsQueueListener;
    }

    private function getPidFile()
    {
        return storage_path('logs/queue.pid');
    }

    /**
     * Get any existing queue listener PID.
     *
     * @return bool|string
     */
    private function getLastQueueListenerPID()
    {
        if (! file_exists($this->getPidFile())) {
            return false;
        }

        return file_get_contents($this->getPidFile());
    }

    /**
     * Save the queue listener PID to a file.
     *
     * @param $pid
     *
     * @return void
     */
    private function saveQueueListenerPID($pid)
    {
        file_put_contents($this->getPidFile(), $pid);
    }

    /**
     * Start the queue listener.
     *
     * @return int
     */
    private function startQueueListener()
    {
        $log_path = config('queue.admin.log_path', storage_path('logs/queue.log'));

        $cli = config('queue.admin.cli', 'php');

        $timeout = config('queue.admin.timeout', 60);

        $sleep = config('queue.admin.sleep', 3);

        $tries = config('queue.admin.tries', 3);

        $command = $cli.' '.base_path().'/artisan queue:work --timeout='.$timeout.' --sleep='.$sleep.' --tries='.$tries.' > '.$log_path.' & echo $!';

        $pid = exec($command);

        return $pid;
    }
}
