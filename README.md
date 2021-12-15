Mark is a high performance API micro framework based on [FastRoute](https://github.com/nikic/FastRoute) and [workerman](https://github.com/walkor/workerman) helps you quickly write APIs with php. It is so simple that the [core codes](https://github.com/passwalls/mark/blob/master/src/App.php) is only about 200 lines.

Probably the fastest PHP API framework in the world. See [the-benchmarker](https://github.com/the-benchmarker/web-frameworks#results).

# Install
It's recommended that you use Composer to install Mark.

`composer require mark-php/mark`

# Usage
start.php
```php
<?php
use Mark\App;

require 'vendor/autoload.php';

$api = new App('http://0.0.0.0:3000');

$api->count = 4; // process count

$api->any('/', function ($requst) {
    return 'Hello world';
});

$api->get('/hello/{name}', function ($requst, $name) {
    return "Hello $name";
});

$api->post('/user/create', function ($requst) {
    return json_encode(['code'=>0 ,'message' => 'ok']);
});

$api->start();
```

Run command `php start.php start -d` 

Going to http://127.0.0.1:3000/hello/world will now display "Hello world".

# Benchmark
https://github.com/the-benchmarker/web-frameworks#results

# Available commands
```
php start.php restart -d
php start.php stop
php start.php status
php start.php connections
```

# License
The Mark Framework is licensed under the MIT license. See License File for more information.
