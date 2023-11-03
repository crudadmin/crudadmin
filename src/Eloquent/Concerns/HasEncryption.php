<?php

namespace Admin\Eloquent\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

trait HasEncryption
{
    /**
     * Decrypt the given encrypted string.
     *
     * @param  string  $value
     * @return mixed
     */
    public function fromEncryptedString($value)
    {
        if ( !$value ){
            return $value;
        }

        try {
            return (static::$encrypter ?? Crypt::getFacadeRoot())->decrypt($value, false);
        } catch (DecryptException $e) {
            return $value;
        }
    }

    public function getEncryptedFields($onlySearchable = false)
    {
        $fields = [];

        foreach ($this->getFields() as $key => $field) {
            //Only encrypted
            if ( !isset($field['encrypted']) ){
                continue;
            }

            //Filter only searchable
            if ( $onlySearchable && $this->isFieldType($key, ['string', 'number', 'decimal', 'date']) == false ){
                continue;
            }


            $fields[$key] = $field;
        }

        return $fields;
    }

    public function setEncryptedAttribute($key, $value)
    {
        $this->withMultiCast(function() use ($key, &$value) {
            //Encrypt support
            if (! is_null($value) && $this->isEncryptedCastable($key) ) {
                $value = $this->castAttributeAsEncryptedString($key, $value);
            }
        });

        $this->attributes[$key] = $value;

        return $this;
    }

    public function getEncryptedAttribute($key)
    {
        $value = $this->attributes[$key] ?? null;

        if ( isset($this->casts[$key]) ){
            return $this->castAttribute($key, $value);
        }

        return $value;
    }

    public function generateEncryptedHash($value)
    {
        if ( is_null($value) || (is_string($value) || is_numeric($value)) == false ){
            return;
        }

        $value = trim($value);
        $value = str_slug($value);

        return md5(hash_hmac('sha256', env('APP_KEY').$value, md5(env('APP_KEY'))));
    }

    public function setEncryptedHashes()
    {
        $encryptedFields = $this->getEncryptedFields(true);

        if ( !count($encryptedFields) ){
            return;
        }

        $hashes = [];

        //Enable only given fields to support hashes
        foreach ($encryptedFields as $key => $value) {
            if ( $value = $this->getAttribute($key) ){
                $hashes[$key] = $this->generateEncryptedHash($value);
            }
        }

        if ( count($hashes) ){
            $this->attributes['_encrypted_hashes'] = json_encode($hashes);
        }
    }
}
