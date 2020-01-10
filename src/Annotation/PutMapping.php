<?php
declare(strict_types=1);

namespace Karthus\Annotation;
/**
 * @Annotation
 * @Target({"METHOD"})
 */
class PutMapping extends Mapping {
    public $methods = ['PUT'];
}
