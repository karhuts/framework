<?php
declare(strict_types=1);

namespace Karthus\Component\Process\Socket;

use Karthus\Component\Process\AbstractProcess;
use Karthus\Exception\Exception;
use Swoole\Coroutine;
use Swoole\Coroutine\Socket;

abstract class AbstractUnixProcess extends AbstractProcess {
    /**
     * AbstractUnixProcess constructor.
     *
     * @param UnixProcessConfig $config
     */
    public function __construct(UnixProcessConfig $config) {
        $config->setEnableCoroutine(true);
        if(empty($config->getSocketFile())){
            throw new Exception("socket file is empty at class ".static::class);
        }
        parent::__construct($config);
    }

    /**
     * @param $arg
     * @return mixed|void
     * @throws \Throwable
     */
    public function run($arg) {
        if (file_exists($this->getConfig()->getSocketFile())) {
            @unlink($this->getConfig()->getSocketFile());
        }
        $socketServer = new Socket(AF_UNIX,SOCK_STREAM, 0);
        if(!$socketServer->bind($this->getConfig()->getSocketFile())){
            throw new Exception(static::class.' bind '.$this->getConfig()->getSocketFile(). ' fail case '.$socketServer->errMsg);
        }
        if(!$socketServer->listen(2048)){
            throw new Exception(static::class.' listen '.$this->getConfig()->getSocketFile(). ' fail case '.$socketServer->errMsg);
        }
        while (1){
            $client = $socketServer->accept(-1);
            if(!$client){
                return;
            }
            if($this->getConfig()->isAsyncCallback()){
                Coroutine::create(function ()use($client){
                    try{
                        $this->onAccept($client);
                    }catch (\Throwable $throwable){
                        $this->onException($throwable,$client);
                    }
                });
            }else{
                try{
                    $this->onAccept($client);
                }catch (\Throwable $throwable){
                    $this->onException($throwable,$client);
                }
            }
        }
    }

    abstract public function onAccept(Socket $socket);
}
