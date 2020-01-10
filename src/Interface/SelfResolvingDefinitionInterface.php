<?php
declare(strict_types=1);
namespace Karthus\Contract;

use Psr\Container\ContainerInterface;

interface SelfResolvingDefinitionInterface {
    /**
     * Resolve the definition and return the resulting value.
     */
    public function resolve(ContainerInterface $container);
    /**
     * Check if a definition can be resolved.
     */
    public function isResolvable(ContainerInterface $container): bool;
}
