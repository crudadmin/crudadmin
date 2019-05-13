<?php

namespace Gogol\Admin\Traits\Support;

trait DataCache
{
    /*
     * Cached data
     */
    protected $buffer = [];

    /*
     * Get property name where will be stored all data
     */
    protected function getBufferKey()
    {
        return isset($this->buffer_key) ? $this->buffer_key : 'buffer';
    }

    /*
     * Change buffer key, where will be stored all property date
     */
    protected function setBufferKey($key)
    {
        $this->buffer_key = $key;
    }

    /**
     * Save data into instance. On second time accessing will be retrieved saved data.
     * @param  string  $key
     * @param  [type]  $data
     * @return $data
     */
    public function cache($key, $data)
    {
        if ( $this->has($key) )
            return $this->get($key);

        //If is passed data callable function
        if ( is_callable($data) )
            $data = call_user_func($data);

        return $this->set($key, $data);
    }

    /*
     * Save property with value into buffer
     */
    public function set($key, $data)
    {
        return $this->{$this->getBufferKey()}[$key] = $data;
    }

    /*
     * Get property from buffer
     */
    public function get($key, $default = null)
    {
        if ( $this->has($key) )
            return $this->{$this->getBufferKey()}[ $key ];
        else
            return $default;
    }

    /*
     * Checks if is property into buffer
     */
    public function has($key)
    {
        return array_key_exists($key, $this->{$this->getBufferKey()});
    }

    /*
     * Push data into array buffer
     */
    public function push($key, $data)
    {
        if ( !array_key_exists($key, $this->{$this->getBufferKey()}) || !is_array($this->{$this->getBufferKey()}[$key]) )
            $this->{$this->getBufferKey()}[$key] = [];

        return $this->{$this->getBufferKey()}[$key][] = $data;
    }
}