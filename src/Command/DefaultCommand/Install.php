<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;

class Install implements CommandInterface {

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "install";
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
        echo welcome();

        @exec('composer dump-autoload');
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        return welcome().'install or reinstall Karthus';
    }
}
