<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use PHPUnit\TextUI\Command;
use Swoole\Coroutine\Scheduler;
use Swoole\ExitException;

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
        $scheduler          = new Scheduler();
        $scheduler->add(function (){
            try{
                Command::main(false);
            }catch (\Throwable $exception){
                // 因为swoole会自己退出，所以我这里临时屏蔽一下 \Swoole\ExitException 的异常就OK
                if(!$exception instanceof ExitException){
                    throw $exception;
                }
            }
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
