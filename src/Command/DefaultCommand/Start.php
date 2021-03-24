<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use Karthus\Config;
use Karthus\Core;
use Karthus\Server;
use Karthus\SystemConst;
use Throwable;

/**
 * 启动脚本
 *
 * Class Start
 *
 * @package Karthus\Command\DefaultCommand
 */
class Start implements CommandInterface{

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return 'start';
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function exec(array $args): ?string {
        opCacheClear();
        $msg  = welcome();
        $conf = Config::getInstance();
        // 设置为静默启动
        if (in_array("d", $args) || in_array("daemonize", $args)) {
            $conf->setConf("MAIN_SERVER.SETTING.daemonize", true);
        }

        //create main Server
        Core::getInstance()->createServer();
        $serverType = $conf->getConf('MAIN_SERVER.SERVER_TYPE');

        switch ($serverType) {
            case Server::SERVER_TYPE_DEFAULT:
                $serverType = 'SWOOLE_SERVER';
                break;
            case Server::SERVER_TYPE_DEFAULT_WEB:
                $serverType = 'SWOOLE_WEB';
                break;
            case Server::SERVER_TYPE_DEFAULT_WEB_SOCKET:
                $serverType = 'SWOOLE_WEB_SOCKET';
                break;
            default:
                $serverType = 'UNKNOWN';
                break;
        }

        $msg .= displayItem('server name', $conf->getConf('SERVER_NAME')) . "\n";
        $msg .= displayItem('main server', $serverType) . "\n";
        $msg .= displayItem('listen address', $conf->getConf('MAIN_SERVER.LISTEN_ADDRESS')) . "\n";
        $msg .= displayItem('listen port', $conf->getConf('MAIN_SERVER.PORT')) . "\n";

        $list   = Server::getInstance()->getSubServerRegister();

        foreach ($list as $serverName => $item) {
            if (empty($item['setting'])) {
                $type = $serverType;
            } else {
                $type = $item['type'] % 2 > 0 ? 'SWOOLE_TCP' : 'SWOOLE_UDP';
            }
            $msg .= displayItem("sub server:{$serverName}", "{$type}@{$item['listenAddress']}:{$item['port']}") . "\n";
        }

        $data       = $conf->getConf('MAIN_SERVER.SETTING');
        if(empty($data['user'])){
            $data['user'] = get_current_user();
        }

        if(!isset($data['daemonize'])){
            $data['daemonize'] = false;
        }

        foreach ($data as $key => $datum){
            $msg .= displayItem($key, $datum) . "\n";
        }

        $msg .= displayItem('Swoole Version', phpversion('swoole')) . "\n";
        $msg .= displayItem('PHP Version', PHP_VERSION) . "\n";
        $msg .= displayItem('Karthus Version', SystemConst::KARTHUS_VERSION) . "\n";
        $msg .= displayItem('Log Dir', KARTHUS_LOG_DIR) . "\n";
        echo $msg;
        Core::getInstance()->start();
        return null;
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        $logo = welcome();
        return $logo . <<<HELP
\e[33mOperation:\e[0m
\e[31m  php karthus start [arg1]\e[0m
\e[33mUsage:\e[0m
\e[36m  to start current karthus server \e[0m
\e[33mArgs:\e[0m
\e[32m  daemonize \e[0m                   run in daemonize
HELP;
    }
}
