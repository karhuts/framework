<?php
declare(strict_types=1);

namespace Karthus\Annotation;
/**
 * @Annotation
 * @Target({"METHOD"})
 */
class PostMapping extends Mapping {
    public $methods = ['POST'];
}
