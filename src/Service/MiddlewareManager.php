<?php
declare(strict_types=1);
namespace Karthus\Service;

class MiddlewareManager {
    /**
     * @var array
     */
    public static $container = [];

    /**
     * @param string $server
     * @param string $path
     * @param string $method
     * @param string $middleware
     */
    public static function addMiddleware(string $server, string $path, string $method, string $middleware): void {
        $method = strtoupper($method);
        static::$container[$server][$path][$method][] = $middleware;
    }

    /**
     * @param string $server
     * @param string $path
     * @param string $method
     * @param array  $middlewares
     */
    public static function addMiddlewares(string $server, string $path, string $method, array $middlewares): void {
        $method = strtoupper($method);
        foreach ($middlewares as $middleware) {
            static::$container[$server][$path][$method][] = $middleware;
        }
    }

    /**
     * @param string $server
     * @param string $rule
     * @param string $method
     * @return array
     */
    public static function get(string $server, string $rule, string $method): array {
        $method = strtoupper($method);
        return static::$container[$server][$rule][$method] ?? [];
    }
}
