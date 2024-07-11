<?php

namespace Admin\Eloquent\Concerns\Auth;

trait HasPasswordSetter
{
    /*
     * Automatically change value in password attribute
     */
    public function setPasswordAttribute($value)
    {
        if ($value && strlen($value) == 60) {
            $this->attributes['password'] = $value;
        } elseif ($value === null) {
            $this->attributes['password'] = null;
        } elseif ($value || is_numeric($value)) {
            $this->attributes['password'] = bcrypt($value);
        }
    }
}
