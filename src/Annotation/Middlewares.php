<?php
declare(strict_types=1);

namespace Karthus\Annotation;

/**
 * @Annotation
 * @Target({"ALL"})
 */
class Middlewares extends AbstractAnnotation {
    /**
     * @var array
     */
    public $middlewares = [];

    /***
     * Middlewares constructor.
     *
     * @param null $value
     */
    public function __construct($value = null) {
        $this->bindMainProperty('middlewares', $value);
    }
}
