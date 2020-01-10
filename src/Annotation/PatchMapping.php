<?php

declare(strict_types=1);

namespace Karthus\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class PatchMapping extends Mapping {
    public $methods = ['PATCH'];
}
