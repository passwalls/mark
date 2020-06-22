Mark is a high performance micro framework based on [workerman](https://github.com/walkor/workerman) helps you quickly write APIs with php.

# Install
It's recommended that you use Composer to install Mark.

`composer require mark/mark`

# Usage
start.php
```php
<?php
use Mark\App;

require 'vendor/autoload.php';

$api = new App('http://0.0.0.0:3000');

$api->any('/', function ($requst) {
    return 'hello world';
});

$api->get('/hello/{name}', function ($requst, $name) {
    return "hello $name";
});

$api->post('/user/create', function ($requst) {
    return json_encode(['code'=>0 ,'message' => 'ok']);
});

$api->start();
```

Run command `php start.php start -d` 

Going to http://localhost:3000/hello/world will now display "Hello world".

# Available commands
```
php start.php restart -d
php start.php stop
php start.php status
php start.php connections
```

# License
The Mark Framework is licensed under the MIT license. See License File for more information.
