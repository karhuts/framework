<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use Karthus\Core;
use Karthus\Config;
use Swoole\Process;

class Restart implements CommandInterface{

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "restart";
    }

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function exec(array $args): ?string {
        $result     = $this->stop();
        if ($result!== true) {
            return $result;
        }
        $this->start();
        return null;
    }

    /**
     * 启动
     *
     * @return null
     * @throws \Throwable
     */
    private function start(){
        opCacheClear();
        $conf = Config::getInstance();
        $conf->setConf("MAIN_SERVER.SETTING.daemonize", true);
        Core::getInstance()->createServer();
        echo "server restart at " . date("Y-m-d H:i:s").PHP_EOL;
        Core::getInstance()->start();
        return null;
    }

    /**
     * 停止服务
     *
     * @return bool|string
     */
    private function stop(){
        $Conf       = Config::getInstance();
        $pidFile    = $Conf->getConf("MAIN_SERVER.SETTING.pid_file");
        if (file_exists($pidFile)) {
            $pid    = (int)file_get_contents($pidFile);
            if (!Process::kill($pid, 0)) {
                return "PID :{$pid} not exist ";
            }
            //强制停止
            Process::kill($pid, SIGKILL);
            //等待5秒
            $time = time();
            while (true) {
                usleep(1000);
                if (!Process::kill($pid, 0)) {
                    if (is_file($pidFile)) {
                        unlink($pidFile);
                    }
                    return true;
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
        return $logo . <<<HELP
\e[33mOperation:\e[0m
\e[31m  php karthus restart\e[0m
\e[33mUsage:\e[0m
\e[36m  to restart current karthus server \e[0m
HELP;
    }
}
