<?php

declare(strict_types=1);

use XNXK\LaravelRedisHelper\Redis;

if (!function_exists('redis')) {
    function redis()
    {
        return app(Redis::class);
    }
}
