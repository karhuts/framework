<?php
declare(strict_types=1);
namespace Karthus\Resolver;

use Karthus\Contract\DefinitionInterface;
use Karthus\Contract\ResolverInterface;
use Karthus\Exception\InvalidDefinitionException;
use Psr\Container\ContainerInterface;
use RuntimeException;

class ResolverDispatcher implements ResolverInterface {
    /**
     * @var null|ObjectResolver
     */
    protected $objectResolver;
    /**
     * @var null|FactoryResolver
     */
    protected $factoryResolver;
    /**
     * @var ContainerInterface
     */
    private $container;
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }
    /**
     * Resolve a definition to a value.
     *
     * @param DefinitionInterface $definition object that defines how the value should be obtained
     * @param array $parameters optional parameters to use to build the entry
     * @throws InvalidDefinitionException if the definition cannot be resolved
     * @return mixed value obtained from the definition
     */
    public function resolve(DefinitionInterface $definition, array $parameters = []) {
        if ($definition instanceof SelfResolvingDefinitionInterface) {
            return $definition->resolve($this->container);
        }
        $resolver = $this->getDefinitionResolver($definition);
        return $resolver->resolve($definition, $parameters);
    }

    /**
     * Check if a definition can be resolved.
     *
     * @param DefinitionInterface $definition object that defines how the value should be obtained
     * @param array               $parameters optional parameters to use to build the entry
     * @return bool
     */
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool {
        if ($definition instanceof SelfResolvingDefinitionInterface) {
            return $definition->isResolvable($this->container);
        }
        $resolver = $this->getDefinitionResolver($definition);
        return $resolver->isResolvable($definition, $parameters);
    }

    /**
     * Returns a resolver capable of handling the given definition.
     *
     * @param DefinitionInterface $definition
     * @return ResolverInterface
     */
    private function getDefinitionResolver(DefinitionInterface $definition): ResolverInterface {
        switch (true) {
            case $definition instanceof ObjectDefinition:
                if (! $this->objectResolver) {
                    $this->objectResolver = new ObjectResolver($this->container, $this);
                }
                return $this->objectResolver;
            case $definition instanceof FactoryDefinition:
                if (! $this->factoryResolver) {
                    $this->factoryResolver = new FactoryResolver($this->container, $this);
                }
                return $this->factoryResolver;
            default:
                throw new RuntimeException('No definition resolver was configured for definition of type ' . get_class($definition));
        }
    }
}
