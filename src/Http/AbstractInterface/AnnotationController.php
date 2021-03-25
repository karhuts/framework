<?php
declare(strict_types=1);

namespace Karthus\Http\AbstractInterface;

abstract class AnnotationController extends Controller {
    private $methodAnnotations = [];
    private $propertyAnnotations = [];
    private $annotation;
}
