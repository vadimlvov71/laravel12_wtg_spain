<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JSON Resource "Envelope" Wrapping
    |--------------------------------------------------------------------------
    |
    | When constructing JSON responses for your API, you may wrap your
    | resources in an "envelope". This setting allows you to disable
    | this wrapping globally for your application, if you wish.
    |
    */

    'wrap' => null,

    /*
    |--------------------------------------------------------------------------
    | JSON Resource Pagination Information
    |--------------------------------------------------------------------------
    |
    | When a paginated resource response is created, Laravel will include
    | pagination information in the response. You may customize the key
    | used for this pagination data within the response here.
    |
    */

    'pagination' => [
        'links' => null,
        'meta' => null,
    ],
];
