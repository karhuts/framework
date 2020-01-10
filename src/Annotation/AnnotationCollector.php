<?php
declare(strict_types=1);

namespace Karthus\Annotation;

use Karthus\Functions\MetadataCollector;

class AnnotationCollector extends MetadataCollector {
    /**
     * @var array
     */
    protected static $container = [];

    /**
     * @param string $class
     * @param string $annotation
     * @param        $value
     */
    public static function collectClass(string $class, string $annotation, $value): void {
        static::$container[$class]['_c'][$annotation] = $value;
    }

    /**
     * @param string $class
     * @param string $property
     * @param string $annotation
     * @param        $value
     */
    public static function collectProperty(string $class, string $property, string $annotation, $value): void {
        static::$container[$class]['_p'][$property][$annotation] = $value;
    }

    /**
     * @param string $class
     * @param string $method
     * @param string $annotation
     * @param        $value
     */
    public static function collectMethod(string $class, string $method, string $annotation, $value): void {
        static::$container[$class]['_m'][$method][$annotation] = $value;
    }

    /**
     * @param string $annotation
     * @return array
     */
    public static function getClassByAnnotation(string $annotation): array {
        $result = [];
        foreach (static::$container as $class => $metadata) {
            if (! isset($metadata['_c'][$annotation])) {
                continue;
            }
            $result[$class] = $metadata['_c'][$annotation];
        }
        return $result;
    }

    /**
     * @param string $class
     * @param string $annotation
     * @return array|\ArrayAccess|mixed|null
     */
    public static function getClassAnnotation(string $class, string $annotation) {
        return static::get($class . '._c.' . $annotation);
    }

    /**
     * @param string $class
     * @param string $method
     * @return array|\ArrayAccess|mixed|null
     */
    public static function getClassMethodAnnotation(string $class, string $method) {
        return static::get($class . '._m.' . $method);
    }

    /**
     * @param string $annotation
     * @return array
     */
    public static function getMethodByAnnotation(string $annotation): array {
        $result = [];
        foreach (static::$container as $class => $metadata) {
            foreach ($metadata['_m'] ?? [] as $method => $_metadata) {
                if ($value = $_metadata[$annotation] ?? null) {
                    $result[] = ['class' => $class, 'method' => $method, 'annotation' => $value];
                }
            }
        }
        return $result;
    }
}
