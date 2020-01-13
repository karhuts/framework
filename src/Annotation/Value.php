<?php
declare(strict_types=1);
namespace Karthus\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Value extends AbstractAnnotation {
    /**
     * @var string
     */
    public $key;
    public function __construct($value = null) {
        parent::__construct($value);
        $this->bindMainProperty('key', $value);
    }
}
