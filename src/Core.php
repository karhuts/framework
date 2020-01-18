<?php
declare(strict_types=1);

namespace Karthus;

use Karthus\AbstractInterface\Event;
use Karthus\Component\Di;
use Karthus\Component\Singleton;
use Karthus\Helper\FileHelper;
use Karthus\Logger\LoggerInterface;
use Karthus\Trigger\Location;
use Karthus\Trigger\TriggerInterface;

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
        KarthusEvent::mainServerCreate(
            Server::getInstance()->getMainEventRegister()
        );
        $this->extraHandler();
        return $this;
    }

    /**
     * 初始化
     *
     * @return $this
     * @throws \Exception
     * @throws \Throwable
     */
    public function initialize(){
        //检查全局文件是否存在.
        $file = KARTHUS_ROOT . '/KarthusEvent.php';
        if(file_exists($file)){
            require_once $file;
            try{
                $ref = new \ReflectionClass('Karthus\KarthusEvent');
                if(!$ref->implementsInterface(Event::class)){
                    exit('global file for KarthusEvent is not compatible for Karthus\KarthusEvent');
                }
                unset($ref);
            }catch (\Throwable $throwable){
                die($throwable->getMessage());
            }
        }else{
            die('global event file missing');
        }
        //先加载配置文件
        $this->loadConfig();
        //执行框架初始化事件
        KarthusEvent::initialize();
        //临时文件和Log目录初始化
        $this->directoryInit();
        //注册错误回调
        $this->registerErrorHandler();
        return $this;
    }

    /**
     * 临时文件和Log目录初始化
     */
    private function directoryInit(){
        $logDir = Config::getInstance()->getConf('LOG_DIR');
        if(empty($logDir)){
            $logDir = KARTHUS_ROOT.'/Logs';
            Config::getInstance()->setConf('LOG_DIR',$logDir);
        }else{
            $logDir = rtrim($logDir,'/');
        }
        if(!is_dir($logDir)){
            FileHelper::createDirectory($logDir);
        }
        defined('KARTHUS_LOG_DIR') or define('KARTHUS_LOG_DIR', $logDir);

        // 设置默认文件目录值(如果自行指定了目录则优先使用指定的)
        if (!Config::getInstance()->getConf('MAIN_SERVER.SETTING.pid_file')) {
            Config::getInstance()->setConf('MAIN_SERVER.SETTING.pid_file', $logDir . '/pid.pid');
        }
        if (!Config::getInstance()->getConf('MAIN_SERVER.SETTING.log_file')) {
            Config::getInstance()->setConf('MAIN_SERVER.SETTING.log_file', $logDir . '/http.log');
        }
    }

    /**
     * 注册捕捉错误
     *
     * @throws \Throwable
     */
    private function registerErrorHandler(){
        //初始化配置Logger
        $logger = Di::getInstance()->get(SystemConst::LOGGER_HANDLER);
        if(!$logger instanceof LoggerInterface){
            $logger = new \Karthus\Logger\Logger(KARTHUS_LOG_DIR);
        }
        Logger::getInstance($logger);

        //初始化追追踪器
        $trigger = Di::getInstance()->get(SystemConst::TRIGGER_HANDLER);
        if(!$trigger instanceof TriggerInterface){
            $trigger = new \Karthus\Trigger\Trigger(Logger::getInstance());
        }
        Trigger::getInstance($trigger);

        //在没有配置自定义错误处理器的情况下，转化为trigger处理
        $errorHandler = Di::getInstance()->get(SystemConst::ERROR_HANDLER);
        if(!is_callable($errorHandler)){
            /**
             * 错误收集器
             *
             * @param      $errorCode
             * @param      $description
             * @param null $file
             * @param null $line
             */
            $errorHandler = function($errorCode, $description, $file = null, $line = null){
                $error = new Location();
                $error->setFile($file);
                $error->setLine($line);
                Trigger::getInstance()->error($description, $errorCode, $error);
            };
        }
        set_error_handler($errorHandler);

        $func = Di::getInstance()->get(SystemConst::SHUTDOWN_FUNCTION);
        if(!is_callable($func)){
            $func = function (){
                $error = error_get_last();
                if(!empty($error)){
                    $location = new Location();
                    $location->setFile($error['file']);
                    $location->setLine($error['line']);
                    Trigger::getInstance()->error($error['message'], $error['type'], $location);
                }
            };
        }
        register_shutdown_function($func);
    }

    /**
     * 加载配置文件
     *
     * @throws \Exception
     */
    private function loadConfig() {
        if($this->isDev){
            $file  = KARTHUS_ROOT . '/Config/dev.php';
        }else{
            $file  = KARTHUS_ROOT . '/Config/produce.php';
        }
        Config::getInstance()->loadConfig($file);
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
