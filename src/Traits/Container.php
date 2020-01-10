<?php
declare(strict_types=1);

namespace Karthus\Traits;

trait Container {
    /**
     * @var array
     */
    protected static $container = [];
    /**
     * {@inheritdoc}
     */
    public static function set(string $id, $value) {
        static::$container[$id] = $value;
    }
    /**
     * {@inheritdoc}
     */
    public static function get(string $id, $default = null) {
        return static::$container[$id] ?? $default;
    }
    /**
     * {@inheritdoc}
     */
    public static function has(string $id) {
        return isset(static::$container[$id]);
    }
    /**
     * {@inheritdoc}
     */
    public static function list(): array {
        return static::$container;
    }
}
