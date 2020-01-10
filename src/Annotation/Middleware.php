<?php
declare(strict_types=1);

namespace Karthus\Annotation;

/**
 * @Annotation
 * @Target({"ALL"})
 */
class Middleware extends AbstractAnnotation {
    /**
     * @var string
     */
    public $middleware = '';

    /**
     * Middleware constructor.
     *
     * @param null $value
     */
    public function __construct($value = null) {
        parent::__construct($value);
        $this->bindMainProperty('middleware', $value);
    }
}
