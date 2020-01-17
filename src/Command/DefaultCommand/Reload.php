<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use Karthus\Config;
use Karthus\Core;
use Swoole\Process;

class Reload implements CommandInterface {

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "reload";
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
        if (in_array('produce', $args)) {
            Core::getInstance()->setDev(false);
        }
        $conf = Config::getInstance();
        $res = '';
        $pidFile = $conf->getConf("MAIN_SERVER.SETTING.pid_file");
        if (file_exists($pidFile)) {
            $sig = SIGUSR1;
            $res = $res . displayItem('Reload Type', "all-worker") . "\n";
            opCacheClear();
            $pid = file_get_contents($pidFile);
            if (!Process::kill($pid, 0)) {
                return "pid :{$pid} not exist ";
            }
            Process::kill($pid, $sig);
            return $res . "send server reload command at " . date("Y-m-d H:i:s");
        } else {
            return "PID file does not exist, please check whether to run in the daemon mode!";
        }
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        $logo = welcome();
        return $logo . <<<HELP_RELOAD
\e[33mOperation:\e[0m
\e[31m  php karthus reload [arg1]\e[0m
\e[33mIntro:\e[0m
\e[36m  you can reload current karthus server\e[0m
\e[33mAgs:\e[0m
\e[32m  produce \e[0m                     load produce.php
HELP_RELOAD;
    }
}
