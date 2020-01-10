<?php
declare(strict_types=1);

namespace Karthus\Contract;

interface AnnotationInterface {
    /**
     * Collect the annotation metadata to a container that you wants.
     */
    public function collectClass(string $className): void;
    /**
     * Collect the annotation metadata to a container that you wants.
     */
    public function collectMethod(string $className, ?string $target): void;
    /**
     * Collect the annotation metadata to a container that you wants.
     */
    public function collectProperty(string $className, ?string $target): void;
}
