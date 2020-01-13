<?php
declare(strict_types=1);
namespace Karthus\Definition;

class PropertyHandlerManager {
    /**
     * @var array
     */
    private static $container = [];

    /**
     * @param string   $annotation
     * @param callable $callback
     */
    public static function register(string $annotation, callable $callback) {
        static::$container[$annotation][] = $callback;
    }
    public static function has(string $annotation): bool {
        return isset(static::$container[$annotation]);
    }

    /**
     * @param string $annotation
     * @return callable[]
     */
    public static function get(string $annotation): array {
        return static::$container[$annotation];
    }
    public static function all(): array {
        return static::$container;
    }
}
