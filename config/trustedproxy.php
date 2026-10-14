<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | The IP addresses and CIDR blocks of reverse proxies (e.g., Hostinger CDN,
    | Cloudflare) that should be trusted when detecting client IP.
    | Set to '*' or comma-separated IP/CIDR list via TRUSTED_PROXIES in .env.
    |
    */

    'proxies' => env('TRUSTED_PROXIES')
        ? (env('TRUSTED_PROXIES') === '*' ? '*' : array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES')))))
        : null,

    /*
    |--------------------------------------------------------------------------
    | Respected Headers
    |--------------------------------------------------------------------------
    |
    | The headers that should be used to detect proxy information.
    |
    */

    'headers' => \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB,

];
