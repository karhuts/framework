<?php
declare(strict_types=1);

namespace Karthus\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class AutoController extends AbstractAnnotation {
    /**
     * @var null|string
     */
    public $prefix = '';

    /**
     * @var string
     */
    public $server = 'http';
}
