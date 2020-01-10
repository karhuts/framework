<?php
declare(strict_types=1);

namespace Karthus\Definition;

use Karthus\Contract\DefinitionInterface;
use Karthus\Functions\ReflectionManager;

class ObjectDefinition implements DefinitionInterface {
    /**
     * @var MethodInjection
     */
    protected $constructorInjection;
    /**
     * @var PropertyInjection[]
     */
    protected $propertyInjections = [];
    /**
     * @var string
     */
    private $name;
    /**
     * @var null|string
     */
    private $className;
    /**
     * @var bool
     */
    private $classExists = false;
    /**
     * @var bool
     */
    private $instantiable = false;
    /**
     * @var bool
     */
    private $needProxy = false;
    /**
     * @var string
     */
    private $proxyClassName;

    /**
     * ObjectDefinition constructor.
     *
     * @param string      $name
     * @param string|null $className
     */
    public function __construct(string $name, string $className = null) {
        $this->name = $name;
        $this->setClassName($className ?? $name);
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return sprintf('Object[%s]', $this->getClassName());
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string|null $className
     */
    public function setClassName(string $className = null): void {
        $this->className = $className;
        $this->updateStatusCache();
    }

    /**
     * @return string
     */
    public function getClassName(): string {
        if ($this->className !== null) {
            return $this->className;
        }
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isClassExists(): bool {
        return $this->classExists;
    }

    /**
     * @return bool
     */
    public function isInstantiable(): bool {
        return $this->instantiable;
    }

    /**
     * @return null|MethodInjection
     */
    public function getConstructorInjection() {
        return $this->constructorInjection;
    }
    public function setConstructorInjection(MethodInjection $injection): self
    {
        $this->constructorInjection = $injection;
        return $this;
    }
    public function completeConstructorInjection(MethodInjection $injection): void
    {
        if ($this->constructorInjection !== null) {
            // Merge
            $this->constructorInjection->merge($injection);
        } else {
            // Set
            $this->constructorInjection = $injection;
        }
    }
    /**
     * @return PropertyInjection[]
     */
    public function getPropertyInjections(): array {
        return $this->propertyInjections;
    }

    /**
     * @param PropertyInjection $propertyInjection
     */
    public function addPropertyInjection(PropertyInjection $propertyInjection): void {
        $this->propertyInjections[$propertyInjection->getPropertyName()] = $propertyInjection;
    }

    /**
     * @return string
     */
    public function getProxyClassName(): string {
        return $this->proxyClassName;
    }

    /**
     * @param string $proxyClassName
     * @return $this
     */
    public function setProxyClassName(string $proxyClassName): self {
        $this->proxyClassName = $proxyClassName;
        return $this;
    }
    /**
     * Determine if the definition need to transfer to a proxy class.
     */
    public function isNeedProxy(): bool {
        return $this->needProxy;
    }

    /**
     * @param bool $needProxy
     * @return $this
     */
    public function setNeedProxy(bool $needProxy): self {
        $this->needProxy = $needProxy;
        return $this;
    }

    /**
     *
     */
    private function updateStatusCache(): void {
        $className = $this->getClassName();
        $this->classExists = class_exists($className) || interface_exists($className);
        if (! $this->classExists) {
            $this->instantiable = false;
            return;
        }
        $this->instantiable = ReflectionManager::reflectClass($className)->isInstantiable();
    }
}
