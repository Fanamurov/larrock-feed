<?php

namespace Larrock\ComponentFeed\Facades;

use Illuminate\Support\Facades\Facade;

class LarrockFeed extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'larrockfeed';
    }

}