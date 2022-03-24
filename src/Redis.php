<?php
namespace XNXK\LaravelRedisHelper;

use Predis\Client;

class Redis
{
    public Client $client;
    private string $type;
    private int $score;
    private int $expire;

    public function __construct(array $config = [])
    {
        $this->type = 'string';
        $this->score = 0;
        $this->expire = -1;
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => $config['host'] ?? '127.0.0.1',
            'port'   => $config['port'] ?? 6379,
        ]);
    }

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function score(int $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function expire(int $expire): static
    {
        $this->expire = $expire;
        return $this;
    }

    public function remember(string $key, callable $callback)
    {
        $map = [
            'string' => [
                'get' => function () use ($key) {
                    return $this->client->get($key);
                },
                'put' => function ($value) use ($key) {
                    $this->client->set($key, $value);
                }
            ],
            'zset' => [
                'get' => function () use ($key, $callback) {
                    $zset = $this->client->zrangebyscore($key, $this->score, $this->score);
                    if (is_array($zset) && count($zset) == 1) {
                        return json_decode(current($zset), true);
                    }

                    // 没有匹配写入cache
                    $return = $callback();
                    $this->client->zadd($key, $this->score, json_encode($return));
                    return $return;
                },
                'put' => function ($value) use ($key) {
                    $this->client->zadd($key, $this->score, json_encode($value));
                }
            ]
        ];

        $getType = (string)$this->client->type($key);
        if (array_key_exists($getType, $map)) {
            return $map[$getType]['get']();
        }

        $getValue = $callback();

        // 没有key写入cache
        $setType = $this->type ?? '';
        if (array_key_exists($setType, $map)) {
            $map[$setType]['put']($getValue);
            if($this->expire !== -1) {
                $this->client->expire($key, $this->expire);
            }
        }

        return $getValue;
    }
}

