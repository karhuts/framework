<?php
declare(strict_types=1);
namespace Karthus\Annotation;

abstract class AbstractAnnotation {
    /**
     * @return string
     */
    abstract public function tagName():string;

    /**
     * @return array
     */
    public function aliasMap():array {
        return [static::class];
    }

    /**
     * @param string|null $raw
     * @return mixed
     */
    abstract public function assetValue(?string $raw);
}
