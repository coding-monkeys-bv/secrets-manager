<?php

namespace CodingMonkeys\SecretsManager\Facades;

use Illuminate\Support\Facades\Facade;

class SecretsManagerFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return SecretsManager::class;
    }
}
