<?php
declare(strict_types=1);

namespace Karthus\Definition;
use Karthus\Contract\DefinitionInterface;

class MethodInjection implements DefinitionInterface {
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var mixed[]
     */
    private $parameters = [];

    /**
     * MethodInjection constructor.
     *
     * @param string $methodName
     * @param array  $parameters
     */
    public function __construct(string $methodName, array $parameters = []) {
        $this->parameters = $parameters;
        $this->methodName = $methodName;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return sprintf('method(%s)', $this->methodName);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return '';
    }

    /**
     * @param string $name
     */
    public function setName(string $name) {
        // The name does not matter for method injections, so do nothing.
    }
    /**
     * @return mixed[]
     */
    public function getParameters(): array {
        return $this->parameters;
    }

    /**
     * @param MethodInjection $definition
     */
    public function merge(self $definition) {
        // In case of conflicts, the current definition prevails.
        $this->parameters = $this->parameters + $definition->parameters;
    }
    /**
     * Reset the target should be resolved.
     * If it is the FactoryDefinition, then the target means $factory property,
     * If it is the ObjectDefinition, then the target means $className property.
     */
    public function setTarget(string $value) {
        $this->methodName = $value;
    }
    /**
     * Determine if the definition need to transfer to a proxy class.
     */
    public function isNeedProxy(): bool {
        // Method injection does not has proxy.
        return false;
    }
}
