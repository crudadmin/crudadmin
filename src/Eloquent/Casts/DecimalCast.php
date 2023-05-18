<?php

namespace Admin\Eloquent\Casts;

use Admin;
use Admin\Core\Casts\LocalizedJsonCast;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class DecimalCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array
     */
    public function get($model, $key, $value, $attributes)
    {
        $decimalLength = $model->getDecimalLength($key);

        //Parse locale values
        if ($model->hasFieldParam($key, 'locale', true)) {
            $value = (new LocalizedJsonCast)->get($model, $key, $value, $attributes);

            if ( is_array($value ) ) {
                foreach ($value as $k => $v) {
                    if (is_null($v)) {
                        unset($value[$k]);
                    } else {
                        $value[$k] = $this->castNumber($v, $decimalLength);
                    }
                }

                return $value;
            }
        }

        //Parse simple values
        return $this->castNumber($value, $decimalLength);
    }

    private function castNumber($value, $decimalLength)
    {
        if ( is_null($value) ){
            return $value;
        }

        return number_format($value, $decimalLength[1], '.', '');
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        //..
    }
}