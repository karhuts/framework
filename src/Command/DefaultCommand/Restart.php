<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use Karthus\Core;

class Restart implements CommandInterface{

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "restart";
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
        if (in_array('produce', $args)) {
            Core::getInstance()->setDev(false);
        }
        $result = $this->stop();
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
        $Conf = Config::getInstance();
        $pidFile = $Conf->getConf("MAIN_SERVER.SETTING.pid_file");
        if (file_exists($pidFile)) {
            $pid = intval(file_get_contents($pidFile));
            if (!\swoole_process::kill($pid, 0)) {
                return "PID :{$pid} not exist ";
            }
            //强制停止
            \swoole_process::kill($pid, SIGKILL);
            //等待5秒
            $time = time();
            while (true) {
                usleep(1000);
                if (!\swoole_process::kill($pid, 0)) {
                    if (is_file($pidFile)) {
                        unlink($pidFile);
                    }
                    return true;
                    break;
                } else {
                    if (time() - $time > 15) {
                        return "stop server fail , try : php easyswoole stop force";
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
        return $logo . <<<HELP_START
\e[33mOperation:\e[0m
\e[31m  php karthus restart [arg1] \e[0m
\e[33mIntro:\e[0m
\e[36m  to restart current karthus server \e[0m
\e[33mArg:\e[0m
\e[32m  produce \e[0m                     load Config/produce.php
HELP_START;
    }
}
