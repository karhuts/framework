<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use PHPUnit\TextUI\Command;
use Swoole\Coroutine\Scheduler;

class PhpUnit implements CommandInterface{

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "phpunit";
    }

    /**
     * 运行PHPUNIT
     *
     * @param array $args
     * @return string|null
     * @throws \Throwable
     */
    public function exec(array $args): ?string {
        $scheduler  = new Scheduler();
        $scheduler->add(function (){
            Command::main(false);
        });
        $scheduler->start();
        return null;
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        return welcome().'php karthus phpunit testDir';
    }
}
