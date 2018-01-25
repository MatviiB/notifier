### Installation

Add Provider
Run next publish config:
```sh
 php artisan vendor:publish --provider=NotifierServiceProvider --tag="config"
```
Run next for publish js to resources folder:
```sh
 php artisan vendor:publish --provider=NotifierServiceProvider --tag="resources"
 ```
Run next for publish js to public folder:
```sh
php artisan vendor:publish --provider=NotifierServiceProvider --tag="public"
``` 
Add worker daemon for ```php artisan notifier:init``` process with Supervisor.
Add published js file to your view or layout.
Done!
