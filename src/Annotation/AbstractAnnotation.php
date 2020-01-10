<?php
declare(strict_types=1);

namespace Karthus\Annotation;

use Karthus\Contract\Able\Arrayable;
use Karthus\Contract\AnnotationInterface;
use Karthus\Functions\ReflectionManager;

use ReflectionProperty;

abstract class AbstractAnnotation implements AnnotationInterface, Arrayable {

    /***
     * AbstractAnnotation constructor.
     *
     * @param null $value
     */
    public function __construct($value = null) {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $val;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function toArray(): array {
        $properties = ReflectionManager::reflectClass(static::class)->getProperties(ReflectionProperty::IS_PUBLIC);
        $result = [];
        foreach ($properties as $property) {
            $result[$property->getName()] = $property->getValue($this);
        }
        return $result;
    }

    /**
     * @param string $className
     */
    public function collectClass(string $className): void {
        AnnotationCollector::collectClass($className, static::class, $this);
    }

    /**
     * @param string      $className
     * @param string|null $target
     */
    public function collectMethod(string $className, ?string $target): void {
        AnnotationCollector::collectMethod($className, $target, static::class, $this);
    }

    /**
     * @param string      $className
     * @param string|null $target
     */
    public function collectProperty(string $className, ?string $target): void {
        AnnotationCollector::collectProperty($className, $target, static::class, $this);
    }

    /**
     * @param string     $key
     * @param array|null $value
     */
    protected function bindMainProperty(string $key, ?array $value) {
        if (isset($value['value'])) {
            $this->{$key} = $value['value'];
        }
    }
}
