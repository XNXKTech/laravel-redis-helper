<?php

namespace XNXK\LaravelRedisHelper\Facades;

use Illuminate\Support\Facades\Facade;

class Redis extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'laravel-redis-helper';
    }
}
