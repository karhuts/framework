<?php
declare(strict_types=1);

namespace Karthus\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class GetMapping extends Mapping {
    public $methods = ['GET'];
}
