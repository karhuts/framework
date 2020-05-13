<?php
declare(strict_types=1);

namespace Karthus;

use Karthus\AbstractInterface\Event;
use Karthus\Component\Di;
use Karthus\Component\Singleton;
use Karthus\Event\EventHelper;
use Karthus\Event\EventRegister;
use Karthus\Helper\FileHelper;
use Karthus\Http\Dispatcher;
use Karthus\Http\Request;
use Karthus\Http\Response;
use Karthus\Logger\LoggerInterface;
use Karthus\Trigger\Location;
use Karthus\Trigger\TriggerInterface;
use Swoole\Http\Status;

class Core {
    use Singleton;

    private $routers = [];

    /**
     *
     * 创建服务
     *
     * @return $this
     * @throws \Throwable
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
        // 先加载配置文件
        $this->loadConfig();
        // 加载路由了
        $this->loadRouter();
        // 执行框架初始化事件
        KarthusEvent::initialize();
        // 临时文件和Log目录初始化
        $this->directoryInit();
        // 注册错误回调
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
        defined('KARTHUS_LOG_DIR') or define('KARTHUS_LOG_DIR', $logDir . "/logs");
        if(!is_dir(KARTHUS_LOG_DIR)){
            FileHelper::createDirectory(KARTHUS_LOG_DIR);
        }

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
        Config::getInstance()->loadConfig(KARTHUS_ROOT . '/Config/Settings.php');
    }

    /**
     * 加载路由配置文件
     */
    private function loadRouter(){
        $file           = KARTHUS_ROOT . '/Config/Router.php';
        $data           = require_once($file);
        $this->routers  = $data;
    }

    /**
     * 获取路由配置信息
     *
     * @return array
     */
    public function getRouters(): array{
        return $this->routers;
    }

    /**
     * 注册默认的回调了
     *
     * @param \Swoole\Server $server
     * @param int            $type
     * @throws \Throwable
     */
    private function registerDefaultCallBack(\Swoole\Server $server, int $type){
        if(in_array($type, [Server::SERVER_TYPE_DEFAULT_WEB, Server::SERVER_TYPE_DEFAULT_WEB_SOCKET],true)){
            $namespace      = Di::getInstance()->get(SystemConst::HTTP_CONTROLLER_NAMESPACE);
            if(empty($namespace)){
                $namespace  = 'Apps\\Controller\\';
            }
            $depth          = intval(Di::getInstance()->get(SystemConst::HTTP_CONTROLLER_MAX_DEPTH));
            $depth          = $depth > 5 ? $depth : 5;
            $max            = intval(Di::getInstance()->get(SystemConst::HTTP_CONTROLLER_POOL_MAX_NUM));
            if($max === 0){
                $max = 500;
            }
            $waitTime       = intval(Di::getInstance()->get(SystemConst::HTTP_CONTROLLER_POOL_WAIT_TIME));
            if($waitTime == 0){
                $waitTime = 5;
            }
            $dispatcher     = new Dispatcher($namespace, $depth, $max);
            $dispatcher->setControllerPoolWaitTime($waitTime);
            $httpExceptionHandler = Di::getInstance()->get(SystemConst::HTTP_EXCEPTION_HANDLER);
            if(!is_callable($httpExceptionHandler)){
                $httpExceptionHandler = function (\Throwable $throwable, Request $request, Response $response){
                    $response->withStatus(Status::INTERNAL_SERVER_ERROR);
                    $response->write(nl2br($throwable->getMessage()."\n".$throwable->getTraceAsString()));
                    Trigger::getInstance()->throwable($throwable);
                };
                Di::getInstance()->set(SystemConst::HTTP_EXCEPTION_HANDLER, $httpExceptionHandler);
            }
            $dispatcher->setHttpExceptionHandler($httpExceptionHandler);

            EventHelper::on($server,EventRegister::onRequest,
                function (\Swoole\Http\Request $request,
                          \Swoole\Http\Response $response) use ($dispatcher){

                $request_psr    = new Request($request);
                $response_psr   = new Response($response);

                try{
                    if(KarthusEvent::beforeRequest($request_psr,$response_psr)){
                        $dispatcher->dispatch($request_psr,$response_psr);
                    }
                }catch (\Throwable $throwable){
                    call_user_func(Di::getInstance()->get(SystemConst::HTTP_EXCEPTION_HANDLER),
                        $throwable, $request_psr, $response_psr);
                }finally{
                    try{
                        KarthusEvent::afterRequest($request_psr,$response_psr);
                    }catch (\Throwable $throwable){
                        call_user_func(Di::getInstance()->get(SystemConst::HTTP_EXCEPTION_HANDLER),
                            $throwable, $request_psr, $response_psr);
                    }
                }
                $response_psr->response();
            });
        }

        $register = Server::getInstance()->getMainEventRegister();
        //注册默认的worker start
        EventHelper::registerWithAdd($register,EventRegister::onWorkerStart, function (\Swoole\Server $server, int $workerId){
            if(isWin() !== true && isMac() !== true){
                $name = Config::getInstance()->getConf('SERVER_NAME');
                if(($workerId < Config::getInstance()->getConf('MAIN_SERVER.SETTING.worker_num'))
                    && $workerId >= 0){
                    @cli_set_process_title("{$name}.Worker.{$workerId}");
                }
            }
        });

        EventHelper::registerWithAdd($register,$register::onWorkerExit,
            function (\Swoole\Server $server, int $workerId){
            //TODO
        });
    }

    /**
     * @todo
     *
     * 注册额外的handler
     */
    private function extraHandler() {}

    /**
     * 启动服务
     */
    public function start() {
        // 命名
        $serverName = Config::getInstance()->getConf('SERVER_NAME');
        if(isWin() === false && isMac() === false){
            @cli_set_process_title($serverName);
            // swoole_set_process_name 可用于 PHP5.2 之上的任意版本
            // swoole_set_process_name 兼容性比 cli_set_process_title 要差
            // 如果存在 cli_set_process_title 函数则优先使用 cli_set_process_title
            // @swoole_set_process_name($serverName);
        }
        //启动
        Server::getInstance()->start();
    }
}
