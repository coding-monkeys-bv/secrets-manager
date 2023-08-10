<?php

namespace CodingMonkeys\SecretsManager;

use Illuminate\Support\Facades\Facade;

class SecretsManagerFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'secrets-manager';
    }
}
