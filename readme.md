# Usage
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

Add worker daemon for ```php artisan notifier:init``` process with Supervisor.

Add published js file to your view or layout.

### Use

Anywhere in your application add next event to send data to frontend:
```php
$data = json_encode(['some' => 'changes']);
event(new Notify($data));
``` 
