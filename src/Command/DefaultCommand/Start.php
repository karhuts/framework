<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use Karthus\Config;
use Karthus\Core;
use Karthus\Server;
use Karthus\SystemConst;

class Start implements CommandInterface{

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return 'start';
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
        opCacheClear();
        $msg  = welcome();
        $mode = 'develop';
        if (!Core::getInstance()->isDev()) {
            $mode = 'produce';
        }

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

        $msg    = $msg . displayItem('main server', $serverType) . "\n";
        $msg    = $msg . displayItem('listen address', $conf->getConf('MAIN_SERVER.LISTEN_ADDRESS')) . "\n";
        $msg    = $msg . displayItem('listen port', $conf->getConf('MAIN_SERVER.PORT')) . "\n";

        $list   = Server::getInstance()->getSubServerRegister();

        $index  = 1;
        foreach ($list as $serverName => $item) {
            if (empty($item['setting'])) {
                $type = $serverType;
            } else {
                $type = $item['type'] % 2 > 0 ? 'SWOOLE_TCP' : 'SWOOLE_UDP';
            }
            $msg = $msg . displayItem("sub server:{$serverName}", "{$type}@{$item['listenAddress']}:{$item['port']}") . "\n";
            $index++;
        }

        $ips    = getLocalIP();
        foreach ($ips as $eth => $val) {
            $msg = $msg . displayItem('ip@' . $eth, $val) . "\n";
        }

        $data = $conf->getConf('MAIN_SERVER.SETTING');
        if(empty($data['user'])){
            $data['user'] = get_current_user();
        }

        if(!isset($data['daemonize'])){
            $data['daemonize'] = false;
        }

        foreach ($data as $key => $datum){
            $msg = $msg . displayItem($key,$datum) . "\n";
        }

        $msg = $msg . displayItem('Swoole Version', phpversion('swoole')) . "\n";
        $msg = $msg . displayItem('PHP Version', phpversion()) . "\n";
        $msg = $msg . displayItem('Karthus Version', SystemConst::KARTHUS_VERSION) . "\n";
        $msg = $msg . displayItem('develop/produce', $mode) . "\n";
        $msg = $msg . displayItem('Log Dir', KARTHUS_LOGGER_DIR) . "\n";
        echo $msg;
        Core::getInstance()->start();
        return null;
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        $logo = welcome();
        return $logo . <<<HELP_START
\e[33mOperation:\e[0m
\e[31m  php karthus start [arg1] [arg2]\e[0m
\e[33mIntro:\e[0m
\e[36m  to start current karthus server \e[0m
\e[33mArg:\e[0m
\e[32m  daemonize \e[0m                   run in daemonize
\e[32m  produce \e[0m                     load produce.php
HELP_START;
    }
}
