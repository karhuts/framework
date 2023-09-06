<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  294953530@qq.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

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
