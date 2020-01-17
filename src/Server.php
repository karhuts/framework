<?php
declare(strict_types=1);
namespace Karthus;

use Karthus\Component\Singleton;
use Karthus\Event\EventRegister;
use Karthus\Process\AbstractProcess;
use Swoole\Process;
use Swoole\Server\Port;

class Server {
    use Singleton;

    public const SERVER_TYPE_DEFAULT = 0;
    public const SERVER_TYPE_DEFAULT_WEB = 1;
    public const SERVER_TYPE_DEFAULT_WEB_SOCKET = 2;

    /**
     * @var \Swoole\Server $swooleServer
     */
    private $swooleServer;
    private $mainServerEventRegister;
    private $subServer = [];
    private $subServerRegister = [];
    private $isStart = false;
    private $customProcess = [];

    /**
     * Server constructor.
     */
    public function __construct() {
        $this->mainServerEventRegister = new EventRegister();
    }
    /**
     * @param string $serverName
     * @return null|\Swoole\Server|Port|\Swoole\WebSocket\Server|\Swoole\Http\Server
     */
    public function getSwooleServer(string $serverName = null) {
        if($serverName === null){
            return $this->swooleServer;
        }else{
            if(isset($this->subServer[$serverName])){
                return $this->subServer[$serverName];
            }
            return null;
        }
    }

    /**
     * 创建
     *
     * @param        $port
     * @param        $type
     * @param string $address
     * @param array  $setting
     * @param mixed  ...$args
     * @return bool
     */
    public function createSwooleServer(int $port = 8000,int $type = 2,
                                       $address = '0.0.0.0',
                                       array $setting = [],
                                       ...$args) :bool {
        switch ($type){
            case self::SERVER_TYPE_DEFAULT:
                $this->swooleServer = new \Swoole\Server($address,$port,...$args);
                break;
            case self::SERVER_TYPE_DEFAULT_WEB:
                $this->swooleServer = new \Swoole\Http\Server($address,$port,...$args);
                break;
            case self::SERVER_TYPE_DEFAULT_WEB_SOCKET:
                $this->swooleServer = new \Swoole\WebSocket\Server($address,$port,...$args);
                break;
            default:{
                Trigger::getInstance()->error("unknown server type :{$type}");
                return false;
            }
        }
        if($this->swooleServer){
            // 设置配置
            $this->swooleServer->set($setting);
        }
        return true;
    }


    /**
     * @param string $serverName
     * @param int    $port
     * @param int    $type
     * @param string $listenAddress
     * @param array  $setting
     * @return EventRegister
     */
    public function addServer(string $serverName,int $port,int $type = SWOOLE_TCP,string $listenAddress = '0.0.0.0',array $setting = []):EventRegister {
        $eventRegister = new EventRegister();
        $subPort = $this->swooleServer->addlistener($listenAddress,$port,$type);
        if(!empty($setting)){
            $subPort->set($setting);
        }
        $this->subServer[$serverName] = $subPort;
        $this->subServerRegister[$serverName] = [
            'port'=>$port,
            'listenAddress'=>$listenAddress,
            'type'=>$type,
            'setting'=>$setting,
            'eventRegister'=>$eventRegister
        ];
        return $eventRegister;
    }

    /**
     * @param AbstractProcess $process
     * @param string|null     $processName
     * @throws \Exception
     */
    public function addProcess(AbstractProcess $process, string $processName=null) {
        if ($processName === null) {
            $processName = $process->getProcessName();
            if ($processName === null) {
                $processClass = get_class($process);
                $processName = basename(str_replace('\\','/',$processClass));
            }
        }

        if (isset($this->customProcess[$processName])) {
            throw new \Exception("Custom process names must be unique :{$processName}");
        }

        $this->customProcess[$processName] = $process->getProcess();
        $this->getSwooleServer()->addProcess($process->getProcess());
    }

    public function getProcess(string $processName) : ? Process {
        return $this->customProcess[$processName];
    }

    /**
     * @return EventRegister
     */
    public function getMainEventRegister():EventRegister {
        return $this->mainServerEventRegister;
    }

    /**
     * 启动服务了
     */
    public function start() {
        $events = $this->getMainEventRegister()->all();
        foreach ($events as $event => $callback){
            $this->getSwooleServer()->on($event, function (...$args) use ($callback) {
                foreach ($callback as $item) {
                    call_user_func($item,...$args);
                }
            });
        }
        $this->registerSubPortCallback();
        $this->isStart = true;
        $this->getSwooleServer()->start();
    }

    /**
     * 判断服务是否启动
     *
     * @return bool
     */
    public function isStart():bool {
        return $this->isStart;
    }

    /**
     * 注册CALLBACK
     */
    private function registerSubPortCallback():void {
        foreach ($this->subServer as $serverName => $subPort ){
            $events = $this->subServerRegister[$serverName]['eventRegister']->all();
            foreach ($events as $event => $callback){
                $subPort->on($event, function (...$args) use ($callback) {
                    foreach ($callback as $item) {
                        call_user_func($item,...$args);
                    }
                });
            }
        }
    }

    /**
     * @return array
     */
    public function getSubServerRegister():array {
        return $this->subServerRegister;
    }
}
