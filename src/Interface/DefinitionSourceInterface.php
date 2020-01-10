<?php
declare(strict_types=1);
namespace Karthus\Contract;

use Karthus\Exception\InvalidDefinitionException;

interface DefinitionSourceInterface {
    /**
     * Returns the DI definition for the entry name.
     *
     * @throws InvalidDefinitionException an invalid definition was found
     * @return null|DefinitionInterface
     */
    public function getDefinition(string $name);
    /**
     * @return array definitions indexed by their name
     */
    public function getDefinitions(): array;
    /**
     * @param mixed $definition
     * @return $this
     */
    public function addDefinition(string $name, $definition);
    public function clearDefinitions(): void;
}
