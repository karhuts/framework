<?php
declare(strict_types=1);
namespace Karthus\Resolver;

use Karthus\Contract\DefinitionInterface;
use Karthus\Contract\ResolverInterface;
use Karthus\Definition\MethodInjection;
use Karthus\Exception\InvalidDefinitionException;
use ReflectionMethod;
use ReflectionParameter;

class ParameterResolver {
    /**
     * @var ResolverInterface
     */
    private $definitionResolver;

    /**
     * ParameterResolver constructor.
     *
     * @param ResolverInterface $definitionResolver
     */
    public function __construct(ResolverInterface $definitionResolver) {
        $this->definitionResolver = $definitionResolver;
    }

    /**
     * @param MethodInjection|null  $definition
     * @param ReflectionMethod|null $method
     * @param array                 $parameters
     * @return array
     * @throws \Karthus\Exception\InvalidDefinitionException
     */
    public function resolveParameters(
        MethodInjection $definition = null,
        ReflectionMethod $method = null,
        array $parameters = []
    ): array {
        $args = [];
        if (! $method) {
            return $args;
        }
        $definitionParameters = $definition ? $definition->getParameters() : [];
        foreach ($method->getParameters() as $index => $parameter) {
            if (array_key_exists($parameter->getName(), $parameters)) {
                $value = &$parameters[$parameter->getName()];
            } elseif (array_key_exists($index, $parameters)) {
                $value = &$parameters[$index];
            } elseif (array_key_exists($index, $definitionParameters)) {
                $value = &$definitionParameters[$index];
            } else {
                if ($parameter->isDefaultValueAvailable() || $parameter->isOptional()) {
                    $args[] = $this->getParameterDefaultValue($parameter, $method);
                    continue;
                }
                throw new InvalidDefinitionException(sprintf(
                    'Parameter $%s of %s has no value defined or guessable',
                    $parameter->getName(),
                    $this->getFunctionName($method)
                ));
            }
            // Nested definitions
            if ($value instanceof DefinitionInterface) {
                // If the container cannot produce the entry, we can use the default parameter value
                if ($parameter->isOptional() && ! $this->definitionResolver->isResolvable($value)) {
                    $value = $this->getParameterDefaultValue($parameter, $method);
                } else {
                    $value = $this->definitionResolver->resolve($value);
                }
            }
            $args[] = &$value;
        }
        return $args;
    }

    /**
     * @param ReflectionParameter $parameter
     * @param ReflectionMethod    $function
     * @return mixed
     * @throws InvalidDefinitionException
     */
    private function getParameterDefaultValue(ReflectionParameter $parameter, ReflectionMethod $function) {
        try {
            return $parameter->getDefaultValue();
        } catch (\ReflectionException $e) {
            throw new InvalidDefinitionException(sprintf(
                'The parameter "%s" of %s has no type defined or guessable. It has a default value, '
                . 'but the default value can\'t be read through Reflection because it is a PHP internal class.',
                $parameter->getName(),
                $this->getFunctionName($function)
            ));
        }
    }

    /**
     * @param ReflectionMethod $method
     * @return string
     */
    private function getFunctionName(ReflectionMethod $method): string {
        return $method->getName() . '()';
    }
}
