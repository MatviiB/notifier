<p>
  <img src="https://scrutinizer-ci.com/g/MatviiB/notifier/badges/build.png?b=master" alt="build passed">
  <img src="https://scrutinizer-ci.com/g/MatviiB/notifier/badges/code-intelligence.svg?b=master" alt="code-intelligence">
  <img src="https://poser.pugx.org/matviib/notifier/license" alt="license">
</p>

### Usage Example
#### For example send new values to chart on some page synchronously to each user.
![laravel socket server](https://gitlab.com/MatviiB/assets/raw/master/ezgif.com-video-to-gif.gif)

### Installation

```
composer require matviib/notifier
```

For Laravel < 5.5 add provider to config/app.php
```php
MatviiB\Notifier\NotifierServiceProvider::class,
```

For publish all files run:
```sh
php artisan vendor:publish
```
and choose "Provider: MatviiB\Notifier\NotifierServiceProvider" if requested.

### Configuration

In `/config/notifier.php` add urls where sockets will be enabled.

Sockets will work on `/` by default.

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

### Usage
Anywhere in your application add next event to send data to some page:
`event(new Notify($data, '/chart'));` or named route `event(new Notify($data, 'chart.index'));`

Event without route or url will send data to EACH page which are listen the sockets.

On front-end part add event listener
```
<script>
    socket.addEventListener('message', function (event) {
        console.log('Message from server', event.data);
    });
</script>
```

## Example with charts

After installation add to config - 
```
'urls' => [
        '/',
        '/chart'
    ]
```
to web.php
```
Route::get('chart', function () {
    return view('chart');
});
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
            event(new Notify($data, '/chart'));
            usleep(rand(100000, 500000));
        }
}
```
Run: `php artisan notifier:init`

Run in another shell:  `php artisan test`

Open `/chart` page.
