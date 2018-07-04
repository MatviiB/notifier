<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Socket server settings
    |--------------------------------------------------------------------------
    |
    | host - host for socket server
    | port - port for socket server
    | connection - socket connection type ws or wss
    | socket_pass - application key for apply system messages
    |
    | Position property requires a string with 2 keywords for vertical and horizontal postion.
    | Format: "<vertical> <horizontal>".
    |
    | Horizontal options: left, center, right
    | Vertical options: top, bottom
    |
    | Default is "top right".
    | Example: <notifications position="top right"/>
    |
    */
    'host' => env('SOCKET_HOST', parse_url(env('APP_URL'), PHP_URL_HOST)),
    'port' => env('SOCKET_PORT', '3000'),
    'connection' => env('SOCKET_CONNECTION', (parse_url(env('APP_URL'), PHP_URL_SCHEME)) === 'https') ? 'wss' : 'ws',
    'socket_pass' => env('SOCKET_PASS', 'secret'),

    'position' => [
        'vertical' => 'top',
        'horizontal' => 'right'
    ]
];