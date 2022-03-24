<?php

use XNXK\LaravelRedisHelper\Redis;

if (!function_exists('redis')) {
    function redis() {
        return app(Redis::class);
    }
}
