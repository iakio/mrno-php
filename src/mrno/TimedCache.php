<?php
namespace mrno;

class TimedCache
{
    private $loader;
    private $cachedValue;

    function __construct(ObjectLoader $loader) {
        $this->cachedValue = [];
        $this->loader = $loader;
    }

    function lookup($key) {
        if (!array_key_exists($key, $this->cachedValue)) {
            $value = $this->loader->load($key);
            $this->cachedValue[$key] = $value;
        } else {
            $value = $this->cachedValue[$key];
        }
        return $value;
    }
}
