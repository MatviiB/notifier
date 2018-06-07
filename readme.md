<p>
  <img src="https://scrutinizer-ci.com/g/MatviiB/notifier/badges/build.png?b=master" alt="build passed">
  <img src="https://scrutinizer-ci.com/g/MatviiB/notifier/badges/code-intelligence.svg?b=master" alt="code-intelligence">
  <img src="https://poser.pugx.org/matviib/notifier/license" alt="license">
</p>

### Usage Example
Send new values to chart on some page synchronously to each user:

`event(new Notify($data, ['chart']));`

Or to users with `id` 3 and 5: `event(new Notify($data, ['chart'], [3, 5]));`

![laravel socket server](https://gitlab.com/MatviiB/assets/raw/master/ezgif.com-video-to-gif.gif)

### Base concepts
This package sends data ONLY to named routes declared as `GET`.

To view available routes you can run `php artisan notifier:init show` command. It will display available routes in the table and initiate the socket server.

`event(new Notify($data));` - send to all routes.

`event(new Notify($data, $routes));` - send to routes in `$routes` array.

`event(new Notify($data, $routes, $users));` - send to routes in `$routes` and only to users in `$users`.

### Installation

```
composer require matviib/notifier
```

For Laravel < 5.5 add provider to config/app.php
```php
MatviiB\Notifier\NotifierServiceProvider::class,
```

For publish notifier config file:
```sh
php artisan vendor:publish
```
and choose "Provider: MatviiB\Notifier\NotifierServiceProvider" if requested.

### Starting server

Add worker daemon for ```php artisan notifier:init``` process with Supervisor,

OR

Start with cron by adding to `$commands`:
```
protected $commands = [
  //
  MatviiB\Notifier\Commands\Notifier::class
];
```

and `schedule` function:

```$schedule->command('notifier:init')->withoutOverlapping()->everyMinute();```

OR

Just run ```php artisan notifier:init``` in terminal. 

Also you can run `php artisan notifier:init show` - this command will show you list of available routes AND start socket server.

### Usage

At first you need to add `@include('notifier::connect')` before you'll use `socket.addEventListener()` to your view or main layout to use it with ALL pages.

Anywhere in your application add next event:

`event(new Notify($data, ['some-route-name']));`

On front-end part add event listener
```
<script>
    socket.addEventListener('message', function (event) {
        console.log('Message from server', event.data);
    });
</script>
```

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
        $i = 0;

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
