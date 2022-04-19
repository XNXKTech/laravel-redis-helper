<?php

declare(strict_types=1);

namespace XNXK\LaravelRedisHelper;

use Predis\Client;

class Redis
{
    public Client $client;
    private string $type;
    private int $score;
    private string $hashKey;
    private int $expire;

    public function __construct(array $config = [])
    {
        $this->type = 'string';
        $this->score = 0;
        $this->hashKey = '';
        $this->expire = -1;

        $options = [
            'parameters' => [
                'password' => $config['password'] ?? getenv('REDIS_PASSWORD') ?: '',
                'database' => $config['database'] ?? getenv('REDIS_DB') ?: 0,
            ],
        ];
        $this->client = new Client([
            'scheme' => 'tcp',
            'host' => $config['host'] ?? getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => $config['port'] ?? getenv('REDIS_PORT') ?: 6379,
        ], $options);
    }

    /**
     * 调用predis方法.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->client->$name(...$arguments);
    }

    /**
     * 设置操作的数据类型.
     *
     * @return $this
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * 设置zset的score.
     *
     * @return $this
     */
    public function score(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    /**
     * 设置hash的key.
     *
     * @return $this
     */
    public function hashKey(string $key): static
    {
        $this->hashKey = $key;

        return $this;
    }

    /**
     * 设置过期时间.
     *
     * @return $this
     */
    public function expire(int $expire): static
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     */
    public function remember(string $key, callable $callback): mixed
    {
        $map = [
            'string' => [
                'get' => function () use ($key) {
                    return $this->client->get($key);
                },
                'put' => function ($value) use ($key): void {
                    $this->client->set($key, $value);
                },
            ],
            'zset' => [
                'get' => function () use ($key, $callback) {
                    $zset = $this->client->zrangebyscore($key, $this->score, $this->score);
                    if (is_array($zset) && count($zset) === 1) {
                        return current($zset);
                    }

                    // 没有匹配写入cache
                    $return = $callback();
                    $this->client->zadd($key, $this->score, $return);

                    return $return;
                },
                'put' => function ($value) use ($key): void {
                    $this->client->zadd($key, $this->score, $value);
                },
            ],
            'hash' => [
                'get' => function () use ($callback, $key) {
                    $hash = $this->client->hget($key, $this->hashKey);
                    if ($hash) {
                        return $hash;
                    }

                    // 没有匹配写入cache
                    $return = $callback();
                    $this->client->hset($key, $this->hashKey, $return);

                    return $return;
                },
                'put' => function ($value) use ($key) {
                    $this->client->hset($key, $this->hashKey, $value);
                },
            ],
        ];

        $getType = (string) $this->client->type($key);
        if (array_key_exists($getType, $map)) {
            return $map[$getType]['get']();
        }

        $getValue = $callback();

        // 没有key写入cache
        $setType = $this->type ?? '';
        if (array_key_exists($setType, $map)) {
            $map[$setType]['put']($getValue);
            if ($this->expire !== -1) {
                $this->client->expire($key, $this->expire);
            }
        }

        return $getValue;
    }
}
