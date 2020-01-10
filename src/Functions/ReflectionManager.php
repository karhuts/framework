<?php
declare(strict_types=1);
namespace Karthus\Functions;

use Hyperf\Di\MetadataCollector;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class ReflectionManager extends MetadataCollector {
    /**
     * @var array
     */
    protected static $container = [];

    /**
     * @param string $className
     * @return ReflectionClass
     */
    public static function reflectClass(string $className): ReflectionClass {
        if (! isset(static::$container['class'][$className])) {
            if (! class_exists($className) && ! interface_exists($className)) {
                throw new InvalidArgumentException("Class {$className} not exist");
            }
            try {
                static::$container[ 'class' ][ $className ] = new ReflectionClass($className);
            } catch (\ReflectionException $e) {
            }
        }
        return static::$container['class'][$className];
    }

    /**
     * @param string $className
     * @param string $method
     * @return ReflectionMethod
     * @throws \ReflectionException
     */
    public static function reflectMethod(string $className, string $method): ReflectionMethod {
        $key = $className . '::' . $method;
        if (! isset(static::$container['method'][$key])) {
            // TODO check interface_exist
            if (! class_exists($className)) {
                throw new InvalidArgumentException("Class {$className} not exist");
            }
            static::$container['method'][$key] = static::reflectClass($className)->getMethod($method);
        }
        return static::$container['method'][$key];
    }

    /**
     * @param string $className
     * @param string $property
     * @return ReflectionProperty
     * @throws \ReflectionException
     */
    public static function reflectProperty(string $className, string $property): ReflectionProperty {
        $key = $className . '::' . $property;
        if (! isset(static::$container['property'][$key])) {
            if (! class_exists($className)) {
                throw new InvalidArgumentException("Class {$className} not exist");
            }
            static::$container['property'][$key] = static::reflectClass($className)->getProperty($property);
        }
        return static::$container['property'][$key];
    }


    public static function clear(): void {
        static::$container = [];
    }
}
