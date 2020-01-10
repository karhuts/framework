<?php
declare(strict_types=1);
namespace Karthus\Definition;

use Karthus\Contract\DefinitionInterface;

class FactoryDefinition implements DefinitionInterface {
    /**
     * @var string
     */
    private $name;
    /**
     * @var callable|string
     */
    private $factory;
    /**
     * @var mixed[]
     */
    private $parameters = [];
    /**
     * @var bool
     */
    private $needProxy = false;

    /**
     * FactoryDefinition constructor.
     *
     * @param string $name
     * @param        $factory
     * @param array  $parameters
     */
    public function __construct(string $name, $factory, array $parameters = []) {
        $this->name = $name;
        $this->factory = $factory;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return 'Factory';
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
     * @return callable|string
     */
    public function getFactory() {
        return $this->factory;
    }
    /**
     * @return mixed[]
     */
    public function getParameters(): array {
        return $this->parameters;
    }
    /**
     * Determine if the definition need to transfer to a proxy class.
     */
    public function isNeedProxy(): bool {
        return $this->needProxy;
    }

    /**
     * @param $needProxy
     * @return $this
     */
    public function setNeedProxy($needProxy): self {
        $this->needProxy = $needProxy;
        return $this;
    }
}
