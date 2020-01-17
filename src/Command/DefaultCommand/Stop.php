<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use Karthus\Karthus\Config;
use Karthus\Karthus\Core;
use Swoole\Process;

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
        $force = false;
        if(in_array('force', $args)){
            $force = true;
        }
        if(in_array('produce', $args)){
            Core::getInstance()->setDev(false);
        }
        $Conf = Config::getInstance();
        $pidFile = $Conf->getConf("MAIN_SERVER.SETTING.pid_file");
        if (file_exists($pidFile)) {
            $pid = intval(file_get_contents($pidFile));
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
                if (!\swoole_process::kill($pid, 0)) {
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
            return "PID file does not exist, please check whether to run in the daemon mode!";
        }
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        $logo = welcome();
        return $logo.<<<HELP_START
\e[33mOperation:\e[0m
\e[31m  php karthus stop [arg1] [arg2]\e[0m
\e[33mIntro:\e[0m
\e[36m  to stop current karthus server \e[0m
\e[33mArg:\e[0m
\e[32m  force \e[0m                   force to kill server
\e[32m  produce \e[0m                 load produce.php
HELP_START;
    }
}
