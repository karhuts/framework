<?php
declare(strict_types=1);

namespace Karthus\Annotation;

abstract class Mapping extends AbstractAnnotation {
    public $methods;

    public $path;

    /**
     * Mapping constructor.
     *
     * @param null $value
     */
    public function __construct($value = null) {
        if (isset($value['path'])) {
            $this->path = $value['path'];
        }
        $this->bindMainProperty('path', $value);
    }
}
