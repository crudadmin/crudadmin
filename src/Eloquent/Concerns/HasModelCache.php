<?php

namespace Admin\Eloquent\Concerns;

trait HasModelCache
{
    /**
     * Which keys should be flushed when model is updated
     *
     * @var array
     */
    // protected $cacheKeysToFlush = [];

    /**
     * Driver for caching rows data
     *
     * @return void
     */
    public function cacheDriver()
    {
        return cache();
    }

    public function getCacheKey($key)
    {
        return 'models.'.$this->getTable().'.'.$key;
    }

    /**
     * Returns cached rows
     *
     * @param  mixed $query
     * @param  mixed $key
     * @param  mixed $duration
     * @param  mixed $callback
     * @return void
     */
    public function scopeGetCached($query, $key, $duration = null)
    {
        $key = $this->getCacheKey($key);
        $duration = is_null($duration) ? now()->addWeek(1) : $duration;

        $data = $this->cacheDriver()->remember($key, $duration, function() {
            return $this->get()->map(function($row){
                return $row->getAttributes();
            })->toArray();
        });

        return $this->hydrate($data);
    }

    /**
     * Automatically flush cache keys when model is updated
     *
     * @return void
     */
    public function flushCacheKeys()
    {
        $flushKeys = $this->getProperty('flushableCacheKeys') ?: [];

        foreach ($flushKeys as $key) {
            $this->cacheDriver()->forget($this->getCacheKey($key));
        }
    }
}