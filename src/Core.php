<?php
declare(strict_types=1);

namespace Karthus\Karthus;

use Karthus\Component\Singleton;

class Core {
    use Singleton;

    /**
     * 是否是测试
     *
     * @var bool
     */
    private $isDev = true;


    /**
     * 设置开发环境
     *
     * @param bool $isDev
     * @return Core
     */
    public function setDev(bool $isDev): Core {
        $this->isDev    = !!$isDev;

        return $this;
    }

    /**
     * 获取开发环境
     *
     * @return bool
     */
    public function isDev() : bool {
        return !!$this->isDev;
    }

    /**
     *
     * @return $this
     */
    public function createServer() :Core{
        $conf = Config::getInstance()->getConf('MAIN_SERVER');
        // 创建swoole服务器了
        Server::getInstance()->createSwooleServer(
            $conf['PORT'],
            $conf['SERVER_TYPE'],
            $conf['LISTEN_ADDRESS'],
            $conf['SETTING'],
            $conf['RUN_MODEL'],
            $conf['SOCK_TYPE']
        );
        $this->registerDefaultCallBack(
            Server::getInstance()->getSwooleServer(),
            $conf['SERVER_TYPE']
        );
        //hook 全局的mainServerCreate事件
        EasySwooleEvent::mainServerCreate(Server::getInstance()->getMainEventRegister());
        $this->extraHandler();
        return $this;
    }

    public function initialize(){

    }

    private function registerDefaultCallBack(\Swoole\Server $server, int $type){

    }

    /***
     * 注册额外的handler
     */
    private function extraHandler() {
        //注册crontab进程
        Crontab::getInstance()->__run();
        //注册Task进程
        $config = Config::getInstance()->getConf('MAIN_SERVER.TASK');
        $config = new TaskConfig($config);
        $config->setTempDir(EASYSWOOLE_TEMP_DIR);
        $config->setServerName(Config::getInstance()->getConf('SERVER_NAME'));
        $config->setOnException(function (\Throwable $throwable){
            Trigger::getInstance()->throwable($throwable);
        });
        TaskManager::getInstance($config)->attachToServer(
            Server::getInstance()->getSwooleServer()
        );
    }

    /**
     * 启动服务
     */
    public function start() {
        //给主进程也命名
        $serverName = Config::getInstance()->getConf('SERVER_NAME');
        @swoole_set_process_name($serverName);
        //启动
        Server::getInstance()->start();
    }
}
