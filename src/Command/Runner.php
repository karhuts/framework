<?php
declare(strict_types=1);
namespace Karthus\Command;

use Karthus\Command\DefaultCommand\Help;
use Karthus\Command\DefaultCommand\Install;
use Karthus\Command\DefaultCommand\PhpUnit;
use Karthus\Command\DefaultCommand\Reload;
use Karthus\Command\DefaultCommand\Restart;
use Karthus\Command\DefaultCommand\Start;
use Karthus\Command\DefaultCommand\Stop;
use Karthus\Command\DefaultCommand\Upgrade;
use Karthus\Component\Singleton;
use Karthus\Core;

class Runner {
    use Singleton;
    private $defaultCommand = 'help';

    /**
     * 注册命令了
     *
     * Runner constructor.
     */
    public function __construct() {
        CommandContainer::getInstance()->set(new Install());
        CommandContainer::getInstance()->set(new Help());
        CommandContainer::getInstance()->set(new Start());
        CommandContainer::getInstance()->set(new Stop());
        CommandContainer::getInstance()->set(new Reload());
        CommandContainer::getInstance()->set(new PhpUnit());
        CommandContainer::getInstance()->set(new Restart());
        CommandContainer::getInstance()->set(new Upgrade());
    }

    /**
     * 运行
     *
     * @param array $args
     * @return string|null
     * @throws \Exception
     * @throws \Throwable
     */
    function run(array $args):?string {
        $command        = array_shift($args);
        if(empty($command)){
            $command    = $this->defaultCommand;
        }elseif($command !== 'install'){
            //预先加载配置
            if(in_array('produce', $args)){
                Core::getInstance()->setDev(false);
            }
            Core::getInstance()->initialize();
        }
        if(!CommandContainer::getInstance()->get($command)){
            $command    = $this->defaultCommand;
        }
        return CommandContainer::getInstance()->hook($command,$args);
    }
}
