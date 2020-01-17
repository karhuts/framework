<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;

class PhpUnit implements CommandInterface{

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "phpunit";
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
        //TODO 单元测试
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        return welcome().'php karthus phpunit testDir';
    }
}
