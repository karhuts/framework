<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use Karthus\Config;
use Karthus\Core;
use Swoole\Process;

/**
 * 停止脚本
 *
 * Class Stop
 *
 * @package Karthus\Command\DefaultCommand
 */
class Stop implements CommandInterface {

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "stop";
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
        $force      = false;
        if(in_array('force', $args)){
            $force  = true;
        }
        $Conf       = Config::getInstance();
        $pidFile    = $Conf->getConf("MAIN_SERVER.SETTING.pid_file");
        if (file_exists($pidFile)) {
            $pid    = intval(file_get_contents($pidFile));
            if (!Process::kill($pid, 0)) {
                return "PID :{$pid} not exist ";
            }
            if ($force) {
                Process::kill($pid, SIGKILL);
            } else {
                Process::kill($pid);
            }
            //等待5秒
            $time = time();
            while (true) {
                usleep(1000);
                if (!Process::kill($pid, 0)) {
                    if (is_file($pidFile)) {
                        unlink($pidFile);
                    }
                    return "server stop at " . date("Y-m-d H:i:s") ;
                    break;
                } else {
                    if (time() - $time > 15) {
                        return "stop server fail , try : php karthus stop force";
                        break;
                    }
                }
            }
            return 'stop server fail';
        } else {
            return "PID file does not exist!";
        }
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        $logo = welcome();
        return $logo.<<<HELP
\e[33mOperation:\e[0m
\e[31m  php karthus stop [arg1]\e[0m
\e[33mUsage:\e[0m
\e[36m  to stop current karthus server \e[0m
\e[33mArgs:\e[0m
\e[32m  force \e[0m                   force to kill server
HELP;
    }
}
