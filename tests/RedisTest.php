<?php
it('remember', function (){
    $response = redis()->type('zset')
        ->score(101)
        ->expire(3600)
        ->remember('test_name', function () {
            return 'test';
        });

    expect($response)->toEqual('test');
});