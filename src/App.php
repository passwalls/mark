<?php

namespace Mark;

use Workerman\Worker;
use Workerman\Protocols\Http\Response;
use Workerman\Connection\TcpConnection;
use FastRoute\Dispatcher;

class App extends Worker
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
    public function put($path, $callback)
    {
        $this->routeInfo['PUT'][] = [$path, $callback];
    }

    /**
     * @param $path
     * @param $callback
     */
    public function patch($path, $callback)
    {
        $this->routeInfo['PATCH'][] = [$path, $callback];
    }

    /**
     * @param $path
     * @param $callback
     */
    public function delete($path, $callback)
    {
        $this->routeInfo['DELETE'][] = [$path, $callback];
    }

    /**
     * @param $path
     * @param $callback
     */
    public function head($path, $callback)
    {
        $this->routeInfo['HEAD'][] = [$path, $callback];
    }

    /**
     * @param $path
     * @param $callback
     */
    public function options($path, $callback)
    {
        $this->routeInfo['OPTIONS'][] = [$path, $callback];
    }

    /**
     * @param $path
     * @param $callback
     */
    public function any($path, $callback)
    {
        $this->routeInfo['GET'][] = [$path, $callback];
        $this->routeInfo['POST'][] = [$path, $callback];
        $this->routeInfo['PUT'][] = [$path, $callback];
        $this->routeInfo['DELETE'][] = [$path, $callback];
        $this->routeInfo['PATCH'][] = [$path, $callback];
        $this->routeInfo['HEAD'][] = [$path, $callback];
        $this->routeInfo['OPTIONS'][] = [$path, $callback];
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
            $callback = $callbacks[$request->path()] ?? null;
            if ($callback) {
                $connection->send($callback($request));
                return null;
            }

            $ret = $this->dispatcher->dispatch($request->method(), $request->path());
            if ($ret[0] === Dispatcher::FOUND) {
                $callback = $ret[1];
                if (!empty($ret[2])) {
                    $args = array_values($ret[2]);
                    $callback = function ($request) use ($args, $callback) {
                        return $callback($request, ... $args);
                    };
                }
                $callbacks[$request->path()] = $callback;
                $connection->send($callback($request));
                return true;
            } else {
                $connection->send(new Response(404, [], '<h1>404 Not Found</h1>'));
            }
        } catch (\Throwable $e) {
            $connection->send(new Response(500, [], (string)$e));
            echo $e;
        }
    }

}
