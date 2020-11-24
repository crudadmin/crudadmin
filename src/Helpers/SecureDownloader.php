<?php

namespace Admin\Helpers;

use Crypt;

class SecureDownloader
{
    private $basepath;

    private static $sessionKey = 'user_secure_downloads';

    public function __construct(string $basepath)
    {
        $this->basepath = $basepath;
    }

    private function getHash()
    {
        return sha1($this->basepath);
    }

    public function getDownloadPath($removeAfterDownload = false)
    {
        session()->put(self::$sessionKey.'.'.$this->getHash(), [
            'basepath' => $this->basepath,
            'signature' => Crypt::encryptString($this->basepath),
            'delete' => $removeAfterDownload,
        ]);

        session()->save();

        return action('\Admin\Controllers\DownloadController@securedUserDownload', $this->getHash());
    }

    public static function getSessionBaseData(string $hash)
    {
        $data = session()->get(self::$sessionKey.'.'.$hash);

        if ( ! $data ){
            return;
        }

        //If signed basepath is okay
        if ( Crypt::decryptString($data['signature']) == $data['basepath'] ){
            return $data;
        }
    }

    public static function getSessionBasePath(string $hash)
    {
        $data = self::getSessionBaseData($hash);

        return @$data['basepath'];
    }
}