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

Publish just config:
```sh
 php artisan vendor:publish --provider=NotifierServiceProvider --tag=config
```

Publish just js to resources folder:
```sh
 php artisan vendor:publish --provider=NotifierServiceProvider --tag=resources
 ```
Publish just js to public folder:
```sh
php artisan vendor:publish --provider=NotifierServiceProvider --tag=public
``` 
Add worker daemon for ```php artisan notifier:init``` process with Supervisor.

Add published js file to your view or layout.

Done!
