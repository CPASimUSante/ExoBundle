<?php

namespace UJM\ExoBundle;

use Claroline\CoreBundle\Library\PluginBundle;

class UJMExoBundle extends PluginBundle
{
    public function getRoutingPrefix()
    {
        return 'exercise';
    }
}