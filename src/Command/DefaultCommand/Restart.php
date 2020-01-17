<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;

class Restart implements CommandInterface{

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "restart";
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
