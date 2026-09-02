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
        $duration = is_null($duration) ? now()->addWeek(1) : $duration;

        $rows = $this->runCached($key, $duration, function() use ($query) {
            return $query->get()->map(function($row){
                return $row->getAttributes();
            })->toArray();
        });

        return $this->hydrate($rows);
    }

    /**
     * Return the first cached row as a hydrated model instance.
     *
     * @param mixed $query
     * @param mixed $key
     * @param mixed $duration
     * @return mixed
     */
    public function scopeFirstCached($query, $key, $duration = null)
    {
        return $query->limit(1)->getCached($key, $duration)->first();
    }

    /**
     * Run cachable query
     *
     * @param  mixed $query
     * @param  mixed $key
     * @param  mixed $duration
     * @param  mixed $callback
     * @return void
     */
    public function runCached($key, $duration = null, $callback = null)
    {
        $key = $this->getCacheKey($key);

        // Ability to pass callback as duration
        if ( is_callable($duration) ) {
            $callback = $duration;
            $duration = null;
        }

        return $this->cacheDriver()->remember($key, $duration, function() use ($callback) {
            return $callback();
        });
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
