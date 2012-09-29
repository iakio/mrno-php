<?php
namespace mrno;

class TimedCache
{
    private $loader;

    function __construct(ObjectLoader $loader) {
        $this->loader = $loader;
    }

    function lookup($key) {
        return $this->loader->load($key);
    }
}
