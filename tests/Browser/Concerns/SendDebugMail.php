<?php

namespace Admin\Tests\Browser\Concerns;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use File;

class SendDebugMail extends Mailable
{
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $files = File::allFiles('tests/Browser/screenshots');
        $logs = File::allFiles(storage_path('logs'));

        $mail = $this->view('dusk_failure', compact('files'));

        foreach ($logs as $file) {
            $mail->attach($file);
        }

        return $mail;
    }
}