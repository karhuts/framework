<?php
declare(strict_types=1);

namespace Karthus\Exception;

use Karthus\Contract\DefinitionInterface;

class InvalidDefinitionException extends Exception {

    /**
     * @param DefinitionInterface $definition
     * @param string              $message
     * @param \Exception|null     $previous
     * @return static
     */
    public static function create(DefinitionInterface $definition, string $message, \Exception $previous = null): self {
        return new self(sprintf('%s' . PHP_EOL . 'Full definition:' . PHP_EOL . '%s', $message, (string) $definition), 0, $previous);
    }
}
