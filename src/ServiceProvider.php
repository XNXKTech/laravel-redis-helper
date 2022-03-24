<?php

declare(strict_types=1);

namespace XNXK\LaravelRedisHelper;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/redis.php',
            'redisHelper'
        );

        $config = config('redis');

        $this->app->bind(Redis::class, static function () use ($config) {
            return new Redis($config);
        });

        $this->app->alias(Redis::class, 'redisHelper');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/redis.php.php' => config_path('redis.php'),
        ], 'config');
    }
}
