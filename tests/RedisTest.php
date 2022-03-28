<?php

declare(strict_types=1);
it('rememberZset', function () {
    $response = redis()->type('zset')
        ->score(101)
        ->expire(3600)
        ->remember('test_zset', function () {
            return json_encode([
                'name' => 'test101',
            ]);
        });

    expect($response)->toEqual('{"name":"test101"}');
});

it('rememberHash', function () {
    $response = redis()->type('hash')
        ->hashKey('business_uuid')
        ->expire(3600)
        ->remember('test_hash', function () {
            return 'jqw4iej6uj48dw8';
        });

    expect($response)->toEqual('jqw4iej6uj48dw8');
});
