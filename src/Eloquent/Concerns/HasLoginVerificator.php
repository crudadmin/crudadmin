<?php

namespace Admin\Eloquent\Concerns;

use Admin\Admin\Buttons\LogoutUser;
use Admin\Notifications\LoginVerificatorNotification;
use Cache;

trait HasLoginVerificator
{
    /**
     * Available admin login verification
     */
    protected $verification = [];

    /*
     * Returns available verifications
     */
    public function getLoginVerifications()
    {
        return $this->verification;
    }

    public function hasLoginVerification()
    {
        return in_array($this->verification_method, ['sms', 'email', 'authenticator']);
    }

    private function getVerificationStorageKey($key = null)
    {
        return 'verificator.'.$this->getKey().($key ? '.'.$key : '');
    }

    public function verificatorCodeEveryMinutes()
    {
        return 5;
    }

    public function generateVerificationCode()
    {
        return rand(100000, 999999);
    }

    public function sendVerificatorCodeWithCache()
    {
        $sessionKey = $this->getVerificationStorageKey('code');

        return Cache::remember($sessionKey, 60 * $this->verificatorCodeEveryMinutes(), function() {
            $code = $this->generateVerificationCode();

            $this->sendVerificatorCode($code);

            return $code;
        });
    }

    public function sendVerificatorCode($code)
    {
        $this->notify(
            new LoginVerificatorNotification($this, $code)
        );
    }

    public function isValidVerificationCode($code)
    {
        $savedCode = Cache::get($this->getVerificationStorageKey('code'));

        return $savedCode == $code;
    }

    public function setLoginVerified($state = false)
    {
        session()->put($this->getVerificationStorageKey('verified'), $state);
        session()->save();

        //We need forget actual code
        if ( $state === true ) {
            Cache::forget($this->getVerificationStorageKey('code'));
            $this->logHistoryAction('login-verificator');
        }
    }

    public function isLoginVerified()
    {
        return $this->hasLoginVerification() === false || session()->get($this->getVerificationStorageKey('verified'), false) === true;
    }
}
