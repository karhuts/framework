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
        $conf       = Config::getInstance();
        $res        = '';
        $pidFile    = $conf->getConf("MAIN_SERVER.SETTING.pid_file");
        if (file_exists($pidFile)) {
            $res .= displayItem('Reload Type', "all-worker") . "\n";
            opCacheClear();
            $pid    = @file_get_contents($pidFile);
            if (!Process::kill($pid, 0)) {
                return "pid :{$pid} not exist ";
            }
            Process::kill($pid, SIGUSR1);
            return "$res send server reload command at " . date("Y-m-d H:i:s");
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
\e[31m  php karthus reload\e[0m
\e[33mUsage:\e[0m
\e[36m  you can reload current karthus server\e[0m
HELP;
    }
}
