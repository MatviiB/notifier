<p>
  <img src="https://scrutinizer-ci.com/g/MatviiB/notifier/badges/build.png?b=master" alt="build passed">
  <img src="https://scrutinizer-ci.com/g/MatviiB/notifier/badges/code-intelligence.svg?b=master" alt="code-intelligence">
  <img src="https://poser.pugx.org/matviib/notifier/license" alt="license">
  <img src="https://poser.pugx.org/matviib/notifier/downloads" alt="downloads">
</p>

### Base concepts

You don't need socket.io, pusher.js, jQuery, bootstrap, node.js, vue.js to start using this package.

This package can be used for sending data synchronously to each user.

This package sends data ONLY to named routes declared as `GET`.

You will get your own socket server on back-end and your clients will connect to it directly, without any third-party requests to be send.

You will have pretty notifications from scratch.

To view available routes you can run `php artisan notifier:init show` command. It will display available routes in the table and initiate the socket server.

| Code | Description |
| --- | --- |
| `event(new Notify($data));` | - send to all routes. |
| `event(new Notify($data, $routes));` |- send to routes in `$routes` array. |
| `event(new Notify($data, $routes, $users));` | - send to routes in `$routes` and only to users in `$users`.|

### Installation

```
composer require matviib/notifier
```

For Laravel < 5.5 add provider to config/app.php
```php
MatviiB\Notifier\NotifierServiceProvider::class,
```

For publish notifier config file and js file for notifications out of the box:
```sh
php artisan vendor:publish
```
and choose "Provider: MatviiB\Notifier\NotifierServiceProvider" if requested.

### Starting server

Add worker daemon for ```php artisan notifier:init``` process with Supervisor,

OR

Just run ```php artisan notifier:init``` in terminal.

If you use SSL you need add to your `nginx` configuration file to server block:
```
    location /websocket {
        proxy_pass http://<your-domain>:<port>; #server host and port
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";

        # Timeout configuration.
        proxy_redirect off;
        proxy_connect_timeout  300;
        proxy_send_timeout     300;
        proxy_read_timeout     300;
   }
```
### Usage

At first you need to add `@include('notifier::connect')` before using `socket.addEventListener()` in your view or main layout to use it on ALL pages.

If you want use notifications from the scratch you need to add `@include('notifier::connect_and_show')` to the view.

Anywhere in your back-end add next event:

`event(new Notify($data));`

On front-end part add event listener
```
<script>
    socket.addEventListener('message', function (event) {
        console.log('Message from server', event.data);
    });
</script>
```

### Use built-in notifications.

Built-in notifications is a vue.js with [vue-notifications](https://github.com/euvl/vue-notification) plugin. If you already use vue.js in application you can just add this plugin yourself.

##### Mapping `$data` parameter.

| Parameter | Description |
| --- | --- |
| `'note' => 1,` | - use notes `true` |
| `'type' => 'warn|success|error|info',` | - type of note |
| `'title' => 'TEXT'` | - title of the note |
| `'text' => 'Lorem ipsum'` | - note's body |

##### Positioning.
In `config/notifier.php` you can modify position where notifications will be shown.

```
// Horizontal options: left, center, right
// Vertical options: top, bottom
'position' => [
        'vertical' => 'bottom',
        'horizontal' => 'right'
    ]
```

## Security

This package allows one way messages - only from server to client.

All messages from client after connecting will be ignored.

From server side messages protected with socket_pass parameter from notifier config.

Channels to users protected with unique hash.


## Example with charts

After installation add to web.php
```
Route::get('chart', function () {
    return view('chart');
})->name('chart');
```
create view `/resources/views/chart.blade.php`

```
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Chart</title>
</head>
<body>
<canvas id="myChart"></canvas>
@include('notifier::connect')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js"></script>
<script type="text/javascript">

    var data = [12, 19, 3, 17, 6, 3, 7, 45, 60, 25];

    var myChart = new Chart(document.getElementById('myChart'), {
        type: 'line',
        data: {
            labels: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            datasets: [{
                label: 'example',
                data: data
            }]
        }
    });

    socket.addEventListener('message', function (event) {
        myChart.data.datasets[0].data.splice(0, 1);
        myChart.data.datasets[0].data.push(JSON.parse(event.data).data.value);
        myChart.update();
    });
</script>
</body>
</html>
```
In .env fix your APP_URL `APP_URL=http://<<U APP URL>>`

Create test command `php artisan make:command Test`
```
use MatviiB\Notifier\Events\Notify; 

...

protected $signature = 'test';

public function handle()
    {
        while ($i < 100) {
            $value = random_int(10, 100);
            $data['value'] = $value;
            event(new Notify($data, ['chart']));
            usleep(rand(100000, 500000));
        }
}
```
Run: `php artisan notifier:init`

Run in another shell:  `php artisan test`

Open `/chart` page.

### Usage Example
Send new values to chart on some page synchronously to each user:

`event(new Notify($data, ['chart']));`

Or to users with `id` 3 and 5: `event(new Notify($data, ['chart'], [3, 5]));`

![laravel socket server](https://gitlab.com/MatviiB/assets/raw/master/ezgif.com-video-to-gif.gif)
