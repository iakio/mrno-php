<?php

namespace mrno;

interface ReloadPolicy
{
    function shouldReload($loadTime, $fetchTime);
}
