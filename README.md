Mark is a high performance micro framework based on [workerman](https://github.com/walkor/workerman) helps you quickly write APIs with php.

# Install
`composer require mark/mark`

# Usage
```php
<?php
use Mark\App;

require 'vendor/autoload.php';

$api = new App('http://0.0.0.0:3000');
$api->get('/', function ($requst) {
    return '';
});
$api->get('/user', function ($requst) {
    return '';
});
$api->get('/user/{id}', function ($requst, $id) {
    return $id;
});
$api->start();
```
