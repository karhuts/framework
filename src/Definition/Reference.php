<?php

declare(strict_types=1);

namespace Karthus\Definition;

use Karthus\Contract\DefinitionInterface;
use Karthus\Contract\SelfResolvingDefinitionInterface;
use Psr\Container\ContainerInterface;
class Reference implements DefinitionInterface, SelfResolvingDefinitionInterface {
    /**
     * Entry name.
     *
     * @var string
     */
    private $name = '';
    /**
     * Name of the target entry.
     *
     * @var string
     */
    private $targetEntryName;
    /**
     * @var bool
     */
    private $needProxy = false;

    /**
     * Reference constructor.
     *
     * @param string $targetEntryName
     */
    public function __construct(string $targetEntryName) {
        $this->targetEntryName = $targetEntryName;
    }
    /**
     * Definitions can be cast to string for debugging information.
     */
    public function __toString(): string {
        return sprintf('get(%s)', $this->targetEntryName);
    }
    /**
     * Returns the name of the entry in the container.
     */
    public function getName(): string {
        return $this->name;
    }
    /**
     * Set the name of the entry in the container.
     */
    public function setName(string $name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getTargetEntryName(): string {
        return $this->targetEntryName;
    }

    /**
     * @param ContainerInterface $container
     * @return mixed
     */
    public function resolve(ContainerInterface $container) {
        return $container->get($this->getTargetEntryName());
    }

    /**
     * @param ContainerInterface $container
     * @return bool
     */
    public function isResolvable(ContainerInterface $container): bool {
        return $container->has($this->getTargetEntryName());
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
