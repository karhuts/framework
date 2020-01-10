<?php

declare(strict_types=1);


namespace Karthus\Router;

/**
 * @method static addRoute($httpMethod, string $route, $handler, array $options = [])
 * @method static addGroup($prefix, callable $callback, array $options = [])
 * @method static get($route, $handler, array $options = [])
 * @method static post($route, $handler, array $options = [])
 * @method static put($route, $handler, array $options = [])
 * @method static delete($route, $handler, array $options = [])
 * @method static patch($route, $handler, array $options = [])
 * @method static head($route, $handler, array $options = [])
 */
class Router {
    /**
     * @var string
     */
    protected static $serverName = 'http';
    /**
     * @var DispatcherFactory
     */
    protected static $factory;

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments) {
        $router = static::$factory->getRouter(static::$serverName);
        return $router->{$name}(...$arguments);
    }

    /**
     * @param string   $serverName
     * @param callable $callback
     */
    public static function addServer(string $serverName, callable $callback) {
        static::$serverName = $serverName;
        call($callback);
        static::$serverName = 'http';
    }

    /**
     * @param DispatcherFactory $factory
     */
    public static function init(DispatcherFactory $factory) {
        static::$factory = $factory;
    }
}
