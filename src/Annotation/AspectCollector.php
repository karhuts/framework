<?php
declare(strict_types=1);

namespace Karthus\Annotation;

use Karthus\Functions\MetadataCollector;

class AspectCollector extends MetadataCollector {
    /**
     * @var array
     */
    protected static $container = [];
    /**
     * @var array
     */
    protected static $aspectRules = [];

    /**
     * @param string $aspect
     * @param array  $classes
     * @param array  $annotations
     */
    public static function setAround(string $aspect, array $classes, array $annotations): void {
        static::set('classes.' . $aspect, $classes);
        static::set('annotations.' . $aspect, $annotations);
        static::$aspectRules[$aspect] = [
            'classes' => $classes,
            'annotations' => $annotations,
        ];
    }

    /**
     * @param string $aspect
     * @return array
     */
    public static function getRule(string $aspect): array {
        return static::$aspectRules[$aspect] ?? [];
    }

    /**
     * @return array
     */
    public static function getRules(): array {
        return static::$aspectRules;
    }
}
