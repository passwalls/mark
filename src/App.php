<?php

namespace Mark;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use FastRoute\Dispatcher;

/**
 * @method void get(string $path, callable $callback)
 * @method void post(string $path, callable $callback)
 * @method void put(string $path, callable $callback)
 * @method void delete(string $path, callable $callback)
 * @method void patch(string $path, callable $callback)
 * @method void head(string $path, callable $callback)
 * @method void options(string $path, callable $callback)
 */
class App extends Worker
{
    /**
     * @var array
     */
    protected $routeInfo = [];

    /**
     * @var Dispatcher
     */
    protected $dispatcher = null;

    /**
     * @var string
     */
    protected $pathPrefix = '';

    /**
     * @var string[]
     */
    protected $verbs = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];

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
    public function any($path, $callback)
    {
        $this->addRoute($this->verbs, $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     */
    public function group($path, $callback)
    {
        $this->pathPrefix = $path;
        $callback($this);
        $this->pathPrefix = '';
    }

    /**
     * @param $method
     * @param $path
     * @param $callback
     */
    public function addRoute($method, $path, $callback)
    {
        foreach ((array)$method as $verb) {
            $this->routeInfo[$verb][] = [$this->pathPrefix . $path, $callback];
        }
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
     * @param Request $request
     * @return null
     */
    public function onMessage($connection, $request)
    {
        static $callbacks = [];
        try {
            $path = $request->path();
            $method = $request->method();
            $key = $method . $path;
            $callback = $callbacks[$key] ?? null;
            if ($callback) {
                $connection->send($callback($request));
                return null;
            }

            $ret = $this->dispatcher->dispatch($method, $path);
            if ($ret[0] === Dispatcher::FOUND) {
                $callback = $ret[1];
                if (!empty($ret[2])) {
                    $args = array_values($ret[2]);
                    $callback = function ($request) use ($args, $callback) {
                        return $callback($request, ... $args);
                    };
                }
                $callbacks[$key] = $callback;
                $connection->send($callback($request));
                return true;
            } else {
                $connection->send(new Response(404, [], '<h1>404 Not Found</h1>'));
            }
        } catch (\Throwable $e) {
            $connection->send(new Response(500, [], (string)$e));
        }
    }

    /**
     * @param string $method
     * @param array $args
     * @return void
     */
    public function __call($method, $args)
    {
        $verb = \strtr($method, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        if (in_array($verb, $this->verbs, true)) {
            $this->addRoute($verb, ...$args);
        }
    }
}
