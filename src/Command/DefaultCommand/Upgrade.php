<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;

class Upgrade implements CommandInterface {

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "upgrade";
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {

    }
}
