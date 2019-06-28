<?php

namespace Admin\Helpers;

use \Illuminate\Database\Eloquent\Collection as BaseCollection;

class AdminCollection extends BaseCollection
{
    //Reset admin model properties for correct displaying attributes
    protected function removeProperties()
    {
        foreach ($this->items as $k => $row)
        {
            foreach (['name', 'title', 'localization', 'active'] as $buffer_key)
            {
                $row->setProperty($buffer_key, null);
            }

            $this->items[$k] = $row;
        }
    }

    /**
     * Get an array with the values of a given key.
     *
     * @param  string  $value
     * @param  string|null  $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($value, $key = null)
    {
        $this->removeProperties();

        return $this->toBase()->pluck($value, $key);
    }
}