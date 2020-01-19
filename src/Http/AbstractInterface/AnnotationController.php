<?php
declare(strict_types=1);

namespace Karthus\Http\AbstractInterface;

use Karthus\Annotation\Annotation;

abstract class AnnotationController extends Controller {
    private $methodAnnotations = [];
    private $propertyAnnotations = [];
    private $annotation;

}
