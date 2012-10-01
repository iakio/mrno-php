<?php
namespace mrno;

class TimestampedValue
{
    public $value;
    public $loadTime;

    function __construct($value, $loadTime) {
        $this->value = $value;
        $this->loadTime = $loadTime;
    }
}

class TimedCache
{
    private $loader;
    private $cachedValue;
    private $clock;
    private $reloadPolicy;

    function __construct(ObjectLoader $loader, Clock $clock, ReloadPolicy $reloadPolicy) {
        $this->cachedValue = [];
        $this->loader = $loader;
        $this->clock = $clock;
        $this->reloadPolicy = $reloadPolicy;
    }

    private function cacheGet($key) {
        if (array_key_exists($key, $this->cachedValue)) {
            return $this->cachedValue[$key];
        }
        return null;
    }

    function lookup($key) {
        $found = $this->cacheGet($key);

        if ($found === null || $this->reloadPolicy->shouldReload($found->loadTime, $this->clock->getCurrentTime())) {
            $found = $this->loadObject($key);
            $this->cachedValue[$key] = $found;
        }
        return $found->value;
    }


    private function loadObject($key)
    {
        $value = new TimestampedValue($this->loader->load($key), $this->clock->getCurrentTime());;
        return $value;
    }
}
