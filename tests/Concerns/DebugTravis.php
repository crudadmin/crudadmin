<?php

namespace Admin\Tests\Concerns;

use Admin\Tests\Browser\Concerns\SendDebugMail;
use File;
use Mail;

trait DebugTravis
{
    /*
     * Drop all tables in database
     */
    public function sendMails()
    {
        if ( env('MAIL_DEVELOPER') ) {
            //Remove sent screenshoots
            $files = File::allFiles('tests/Browser/screenshots');

            if ( count($files) > 0 ) {
                Mail::to(env('MAIL_DEVELOPER'))->send(new SendDebugMail);

                foreach ($files as $file) {
                    unlink($file);
                }
            }

        }
    }
}
