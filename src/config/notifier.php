<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Socket server settings
    |--------------------------------------------------------------------------
    |
    | host - host for socket server
    | port - port for socket server
    | socket_pass - application key for apply system messages
    |
    */
    'host' => preg_replace('/(.*\/\/)/', '', env('APP_URL')),
    'port' => env('SOCKET_PORT', '3000'),
    'socket_pass' => env('SOCKET_PASS', '')

];