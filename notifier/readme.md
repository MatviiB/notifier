### Installation

1. Add Provider
2. Run next publish config:
```sh
 php artisan vendor:publish --provider=NotifierServiceProvider --tag="config"
```
3. Run next for publish js to resources folder:
```sh
 php artisan vendor:publish --provider=NotifierServiceProvider --tag="resources"
 ```
4. Run next for publish js to public folder:
```sh
php artisan vendor:publish --provider=NotifierServiceProvider --tag="public"
``` 
3. Add worker daemon for ```php artisan notifier:init``` process with Supervisor.
4. Add published js file to your view or layout.
5. Done!