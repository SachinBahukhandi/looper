<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Facade;

class ImportHelperFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'import';
    }
}
