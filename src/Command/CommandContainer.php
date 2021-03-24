<?php
declare(strict_types=1);
namespace Karthus\Command;

use Karthus\Component\Singleton;

class CommandContainer {
    use Singleton;

    private $container = [];

    /**
     * 设置命令
     *
     * @param CommandInterface $command
     * @param bool             $cover
     */
    public function set(CommandInterface $command,$cover = false): void{
        if(!isset($this->container[strtolower($command->commandName())]) || $cover){
            $this->container[strtolower($command->commandName())] = $command;
        }
    }

    /**
     * 获取命令
     *
     * @param $key
     * @return CommandInterface|null
     */
    public function get($key): ?CommandInterface {
        $key = strtolower($key);
        return $this->container[$key] ?? null;
    }

    /**
     * 获取命令列表
     *
     * @return array
     */
    public function getCommandList(): array {
        return array_keys($this->container);
    }

    /**
     * 钩子，不造以后会不会用到
     *
     * @param       $commandName
     * @param array $args
     * @return string|null
     */
    public function hook($commandName, array $args):?string {
        $handler = $this->get($commandName);
        if($handler){
            return $handler->exec($args);
        }
        return null;
    }
}
