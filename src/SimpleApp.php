<?php

namespace Mark;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use FastRoute\Dispatcher;

class SimpleApp extends Worker
{
    /**
     * @var array
     */
    protected $routeInfo = [];

    /**
     * @var null
     */
    protected $dispatcher = null;

    /**
     * App constructor.
     * @param string $socket_name
     * @param array $context_option
     */
    public function __construct($socket_name = '', array $context_option = array())
    {
        parent::__construct($socket_name, $context_option);
        $this->onMessage = [$this, 'onMessage'];
    }

    /**
     * @param $path
     * @param $callback
     */
    public function get($path, $callback)
    {
        $this->routeInfo['GET'][] = [$path, $callback];
    }

    /**
     * @param $path
     * @param $callback
     */
    public function post($path, $callback)
    {
        $this->routeInfo['POST'][] = [$path, $callback];
    }

    /**
     * @param $path
     * @param $callback
     */
    public function any($path, $callback)
    {
        $this->routeInfo['GET'][] = [$path, $callback];
        $this->routeInfo['POST'][] = [$path, $callback];
    }

    /**
     * start
     */
    public function start()
    {
        $this->dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
           foreach ($this->routeInfo as $method => $callbacks) {
               foreach ($callbacks as $info) {
                   $r->addRoute($method, $info[0], $info[1]);
               }
           }
        });

        \Workerman\Worker::runAll();
    }

    /**
     * @param TcpConnection $connection
     * @param string $request
     * @return null
     */
    public function onMessage($connection, $request)
    {
        static $callbacks = [];
        try {
            $callback = $callbacks[$request] ?? null;
            if ($callback) {
                $connection->send(static::buildResponse($callback($request)), true);
                return null;
            }

            list($method, $path, $protocol) = \explode(' ', $request);
            $ret = $this->dispatcher->dispatch($method, $path);
            if ($ret[0] === Dispatcher::FOUND) {
                $callback = $ret[1];
                if (!empty($ret[2])) {
                    $args = array_values($ret[2]);
                    $callback = function ($request) use ($args, $callback) {
                        return $callback($request, ... $args);
                    };
                }
                $callbacks[$request] = $callback;
                $connection->send(static::buildResponse($callback($request)), true);
                return true;
            } else {
                $connection->send(static::buildResponse('<h1>404 Not Found</h1>', 404), true);
            }
        } catch (\Throwable $e) {
            $connection->send(static::buildResponse((string)$e, 500), true);
            echo $e;
        }
    }

    /**
     * @param $body
     * @param int $status
     * @param string $reason
     * @return string
     */
    public static function buildResponse($body, $status = 200, $reason = 'ok')
    {
        $body_len = \strlen($body);
        return "HTTP/1.1 {$status} {$reason}\r\nServer: mark\r\nContent-Type: text/html;charset=utf-8\r\nContent-Length: {$body_len}\r\nConnection: keep-alive\r\n\r\n{$body}";
    }

}