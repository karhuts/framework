<?php
declare(strict_types=1);

namespace Karthus\Contract;

use ReflectionType;

interface MethodDefinitionCollectorInterface {
    /**
     * Retrieve the metadata for the parameters of the method.
     *
     * @param string $class
     * @param string $method
     * @return ReflectionType[]
     */
    public function getParameters(string $class, string $method): array;

    /**
     * Retrieve the metadata for the return value of the method.
     *
     * @param string $class
     * @param string $method
     * @return ReflectionType
     */
    public function getReturnType(string $class, string $method): ReflectionType;
}
