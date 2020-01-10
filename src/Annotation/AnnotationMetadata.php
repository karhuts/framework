<?php
declare(strict_types=1);

namespace Karthus\Annotation;

class AnnotationMetadata {
    public $class = [];
    public $method = [];

    /**
     * AnnotationMetadata constructor.
     *
     * @param array $class
     * @param array $method
     */
    public function __construct(array $class, array $method) {
        $this->class = $class;
        $this->method = $method;
    }
}
