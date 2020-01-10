<?php

declare(strict_types=1);

namespace Karthus\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class DeleteMapping extends Mapping {
    public $methods = ['DELETE'];
}
