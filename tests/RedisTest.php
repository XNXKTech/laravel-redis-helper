<?php

declare(strict_types=1);
it('remember', function () {
    $response = redis()->type('zset')
        ->score(101)
        ->expire(3600)
        ->remember('test_name', function () {
            return 'test101';
        });

    expect($response)->toEqual('test101');
});
