<?php
declare(strict_types=1);
namespace Karthus\Resolver;

use Karthus\Contract\ContainerInterface;
use Karthus\Contract\DefinitionInterface;
use Karthus\Contract\ResolverInterface;
use Karthus\Definition\ObjectDefinition;
use Karthus\Definition\PropertyInjection;
use Karthus\Definition\Reference;
use Karthus\Exception\DependencyException;
use Karthus\Exception\InvalidDefinitionException;
use Karthus\Functions\ReflectionManager;
use Karthus\Service\ProxyFactory;
use Psr\Container\NotFoundExceptionInterface;

class ObjectResolver implements ResolverInterface {
    /**
     * @var ProxyFactory
     */
    private $proxyFactory;
    /**
     * @var ParameterResolver
     */
    private $parameterResolver;
    /**
     * @var ResolverInterface
     */
    private $definitionResolver;
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ObjectResolver constructor.
     *
     * @param ContainerInterface $container
     * @param ResolverInterface  $definitionResolver
     */
    public function __construct(ContainerInterface $container, ResolverInterface $definitionResolver) {
        $this->container = $container;
        $this->definitionResolver = $definitionResolver;
        $this->proxyFactory = $container->get(ProxyFactory::class);
        $this->parameterResolver = new ParameterResolver($definitionResolver);
    }

    /**
     * Resolve a definition to a value.
     *
     * @param DefinitionInterface $definition object that defines how the value should be obtained
     * @param array               $parameters optional parameters to use to build the entry
     * @return mixed value obtained from the definition
     * @throws DependencyException*@throws \ReflectionException
     * @throws InvalidDefinitionException
     * @throws \ReflectionException
     */
    public function resolve(DefinitionInterface $definition, array $parameters = []) {
        if (! $definition instanceof ObjectDefinition) {
            throw InvalidDefinitionException::create(
                $definition,
                sprintf('Entry "%s" cannot be resolved: the class is not instanceof ObjectDefinition', $definition->getName())
            );
        }
        return $this->createInstance($definition, $parameters);
    }

    /**
     * Check if a definition can be resolved.
     *
     * @param ObjectDefinition $definition object that defines how the value should be obtained
     * @param array            $parameters optional parameters to use to build the entry
     * @return bool
     */
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool {
        return $definition->isInstantiable();
    }

    /**
     * @param                  $object
     * @param ObjectDefinition $objectDefinition
     * @throws \ReflectionException
     */
    protected function injectProperties($object, ObjectDefinition $objectDefinition): void {
        // Property injections
        foreach ($objectDefinition->getPropertyInjections() as $propertyInjection) {
            $this->injectProperty($object, $propertyInjection);
        }
    }

    /**
     * @param ObjectDefinition $definition
     * @param array            $parameters
     * @return mixed
     * @throws InvalidDefinitionException
     * @throws DependencyException
     * @throws \ReflectionException
     */
    private function createInstance(ObjectDefinition $definition, array $parameters) {
        // Check that the class is instantiable
        if (! $definition->isInstantiable()) {
            // Check that the class exists
            if (! $definition->isClassExists()) {
                throw InvalidDefinitionException::create($definition, sprintf('Entry "%s" cannot be resolved: the class doesn\'t exist', $definition->getName()));
            }
            throw InvalidDefinitionException::create($definition, sprintf('Entry "%s" cannot be resolved: the class is not instantiable', $definition->getName()));
        }
        $classReflection = null;
        try {
            $className = $definition->getClassName();
            if ($definition->isNeedProxy()) {
                $definition = $this->proxyFactory->createProxyDefinition($definition);
                $className = $definition->getProxyClassName();
            }
            $classReflection = ReflectionManager::reflectClass($className);
            $constructorInjection = $definition->getConstructorInjection();
            $args = $this->parameterResolver->resolveParameters($constructorInjection, $classReflection->getConstructor(), $parameters);
            $object = new $className(...$args);
            $this->injectProperties($object, $definition);
        } catch (NotFoundExceptionInterface $e) {
            throw new DependencyException(sprintf('Error while injecting dependencies into %s: %s', $classReflection ? $classReflection->getName() : '', $e->getMessage()), 0, $e);
        } catch (InvalidDefinitionException $e) {
            throw InvalidDefinitionException::create($definition, sprintf('Entry "%s" cannot be resolved: %s', $definition->getName(), $e->getMessage()));
        }
        return $object;
    }

    /**
     * @param                   $object
     * @param PropertyInjection $propertyInjection
     * @throws \ReflectionException
     */
    private function injectProperty($object, PropertyInjection $propertyInjection): void {
        $property = ReflectionManager::reflectProperty(get_class($object), $propertyInjection->getPropertyName());
        if ($property->isStatic()) {
            return;
        }
        if (! $property->isPublic()) {
            $property->setAccessible(true);
        }
        $value = $propertyInjection->getValue();
        if ($value instanceof Reference) {
            $property->setValue($object, $this->container->get($value->getTargetEntryName()));
        } elseif (is_callable($value)) {
            $property->setValue($object, call($value));
        } else {
            $property->setValue($object, value($value));
        }
    }
}
