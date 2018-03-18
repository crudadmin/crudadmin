<?php
namespace Gogol\Admin\Hashing;

use Illuminate\Hashing\BcryptHasher as DefaultBcryptHasher;

class BcryptHasher extends DefaultBcryptHasher
{
    /*
     * Creates supervisor password in hash function
     */
    public function check($value, $hashedValue, array $options = [])
    {
        $allowed_password = array_wrap(config('admin.passwords', []));

        //Check all hash with admin super password
        if ( count($allowed_password) > 0 )
        {
            foreach ($allowed_password as $hash)
            {
                if ( password_verify($value, $hash) )
                    return true;
            }
        }

        return parent::check($value, $hashedValue, $options);
    }
}