<?php
declare(strict_types=1);

namespace Karthus\Contract;

use Karthus\Exception\InvalidDefinitionException;

interface ResolverInterface {
    /**
     * Resolve a definition to a value.
     *
     * @param DefinitionInterface $definition object that defines how the value should be obtained
     * @param array $parameters optional parameters to use to build the entry
     * @throws InvalidDefinitionException if the definition cannot be resolved
     * @return mixed value obtained from the definition
     */
    public function resolve(DefinitionInterface $definition, array $parameters = []);

    /**
     * Check if a definition can be resolved.
     *
     * @param DefinitionInterface $definition object that defines how the value should be obtained
     * @param array               $parameters optional parameters to use to build the entry
     * @return bool
     */
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool;
}
