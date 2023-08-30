<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  min@bluecity.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus;

use SplObjectStorage;
use StdClass;
use WeakMap;

use function property_exists;

/**
 * Class Context.
 */
class Context
{
    /**
     * @var SplObjectStorage|WeakMap
     */
    protected static $objectStorage;

    /**
     * @var StdClass
     */
    protected static $object;

    /**
     * @return mixed
     */
    public static function get(string $key = null)
    {
        $obj = static::getObject();
        if ($key === null) {
            return $obj;
        }
        return $obj->{$key} ?? null;
    }

    public static function set(string $key, $value): void
    {
        $obj = static::getObject();
        $obj->{$key} = $value;
    }

    public static function delete(string $key): void
    {
        $obj = static::getObject();
        unset($obj->{$key});
    }

    public static function has(string $key): bool
    {
        $obj = static::getObject();
        return property_exists($obj, $key);
    }

    public static function destroy(): void
    {
        unset(static::$objectStorage[static::getKey()]);
    }

    protected static function getObject(): StdClass
    {
        if (! static::$objectStorage) {
            static::$objectStorage = class_exists(WeakMap::class) ? new WeakMap() : new SplObjectStorage();
            static::$object = new StdClass();
        }
        $key = static::getKey();
        if (! isset(static::$objectStorage[$key])) {
            static::$objectStorage[$key] = new StdClass();
        }
        return static::$objectStorage[$key];
    }

    /**
     * @return mixed
     */
    protected static function getKey()
    {
        return static::$object;
    }
}
