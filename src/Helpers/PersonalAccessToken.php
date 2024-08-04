<?php

namespace Admin\Helpers;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public static function booted()
    {
        // Prevent updating the last_used_at column on every request
        static::updating(function (self $accessToken) {
            $dirty = $accessToken->getDirty();

            if (count($dirty) === 1 && isset($dirty['last_used_at'])) {
                return false;
            }

            return true;
        });
    }
}