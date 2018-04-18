# Usage Example
#### For example send new values to chart on some page synchronously to each user.
![laravel socket server](https://gitlab.com/MatviiB/assets/raw/master/ezgif.com-video-to-gif.gif)

### Installation

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

### Starting server.

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

### Use

Anywhere in your application add next event to send data to frontend:
```php
$data = json_encode(['some' => 'changes']);
event(new Notify($data));
``` 
