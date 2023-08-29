<?php
declare(strict_types=1);
namespace karthus\route;

trait RouteCollectionTrait
{

    public const GET = "GET";
    public const POST = "POST";
    public const DELETE = "DELETE";
    public const PUT = "PUT";
    public const PATCH = "PATCH";
    public const HEAD = "HEAD";
    public const OPTIONS = "OPTIONS";

    abstract public static function addRoute(array|string $methods, string $path, $handler): Route;

    public static function delete(string $path, $handler): Route
    {
        return static::addRoute(static::DELETE, $path, $handler);
    }

    public static function get(string $path, $handler): Route
    {
        return static::addRoute(static::GET, $path, $handler);
    }

    public static function head(string $path, $handler): Route
    {
        return static::addRoute(static::HEAD, $path, $handler);
    }

    public static function options(string $path, $handler): Route
    {
        return static::addRoute(static::OPTIONS, $path, $handler);
    }

    public static function patch(string $path, $handler): Route
    {
        return static::addRoute(static::PATCH, $path, $handler);
    }

    public static function post(string $path, $handler): Route
    {
        return static::addRoute(static::POST, $path, $handler);
    }

    public static function put(string $path, $handler): Route
    {
        return static::addRoute(static::PUT, $path, $handler);
    }

    /**
     * @param array $method
     * @param string $path
     * @param $handler
     * @return Route
     */
    public static function add(array $method, string $path, $handler): Route {
        return static::addRoute($method, $path, $handler);
    }

    /**
     * @param string $path
     * @param callable|mixed $callback
     * @return Route
     */
    public static function any(string $path, mixed $callback): Route
    {
        return static::addRoute([static::GET, static::POST, static::PUT, static::DELETE,
            static::PATCH, static::HEAD, static::OPTIONS], $path, $callback);
    }
}
