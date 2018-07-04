<?php

function notifier_js()
{
    if ($route = \Route::current()->getName()) {
        if (config('notifier.connection') === 'wss') {
            $connection = 'var socket=new WebSocket("' . config('notifier.connection') . '://' . config('notifier.host') . '/websocket");';
        } else {
            $connection = 'var socket=new WebSocket("' . config('notifier.connection') . '://' . config('notifier.host') . ':' . config('notifier.port') . '");';
        }

        echo '<script>' . $connection . 'socket.onopen=function(){' . 'socket.send("' . $route . getUniqueId() . '");' . 'console.log("Connection established!");' . '};' . '</script>';
    }
}

function getUniqueId()
{
    if ($user_id = \Auth::id()) {
        $unique_id = uniqid();

        \Cache::put(
            'notifier:' . $unique_id,
            $user_id,
            config('session.lifetime')
        );

        return ':' . $unique_id;
    }
}
