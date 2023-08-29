<?php
declare(strict_types=1);

namespace karthus\route;

interface RouteCollectionInterface
{
    public static function any(string $path, mixed $callback): Route;
    public static function delete(string $path, $handler): Route;
    public static function get(string $path, $handler): Route;
    public static function head(string $path, $handler): Route;
    public static function options(string $path, $handler): Route;
    public static function patch(string $path, $handler): Route;
    public static function post(string $path, $handler): Route;
    public static function put(string $path, $handler): Route;
    public static function add(array $method, string $path, $handler): Route;
}
