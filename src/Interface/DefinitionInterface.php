<?php
declare(strict_types=1);

namespace Karthus\Contract;

interface DefinitionInterface {
    /**
     * Definitions can be cast to string for debugging information.
     */
    public function __toString(): string;
    /**
     * Returns the name of the entry in the container.
     */
    public function getName(): string;
    /**
     * Set the name of the entry in the container.
     */
    public function setName(string $name);
    /**
     * Determine if the definition need to transfer to a proxy class.
     */
    public function isNeedProxy(): bool;
}
