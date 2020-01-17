<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;

class Config implements CommandInterface{

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        // TODO: Implement commandName() method.
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
        // TODO: Implement exec() method.
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        // TODO: Implement help() method.
    }
}
