<?php
declare(strict_types=1);
namespace Karthus\Driver\Redis;

use Karthus\Exception\ConnectFail;

class Redis {
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $serverInfo;
    /**
     * @var array
     */
    protected $nodeClientList;
    /**
     * @var \Redis;
     */
    protected $redisClient;

    /**
     * Redis constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->config   = $config;
    }

    /**
     * @return \Redis
     * @throws \Throwable
     */
    public function getRedisClient(): \Redis {
        $this->connect();
        return $this->redisClient;
    }

    /**
     * 连接
     *
     * @throws \Throwable
     */
    public function connect(){
        $serverList         = $this->getServers();
        $idx                = array_rand($serverList);
        $this->serverInfo   = $serverList[$idx];

        //然后开始连接
        if(isset($this->nodeClientList[$this->serverInfo])
            && $this->nodeClientList[$this->serverInfo] instanceof Redis
            && $this->nodeClientList[$this->serverInfo]->connected){
            $this->redisClient = $this->nodeClientList[$this->serverInfo];
        }else {
            try {
                $__         = explode(':', $this->serverInfo);
                $host       = $__[0];
                $port       = $__[1];

                $client     = new \Redis();
                $ret        = $client->pconnect($host, (int) $port);

                if(!$ret){
                    throw new ConnectFail("connect to {$this->config->getHost()} at port {$this->config->getPort()} fail: {$errno} {$error}");
                }else {
                    $this->nodeClientList[$this->serverInfo] = $client;
                    $this->redisClient                       = $client;
                }
            }catch (\Throwable $exception){
                throw $exception;
            }
        }
    }

    /**
     * 断开连接
     *
     * @throws \RedisException
     */
    public function disconnect() {
        if($this->redisClient->ping()){
            $this->redisClient->close();
            unset($this->nodeClientList[$this->serverInfo]);
        }
    }


    /**
     * @return Config
     */
    public function getConfig(): Config{
        return $this->config;
    }

    /**
     * @return array
     */
    public function getServers(): array {
        return $this->getConfig()->getServerList();
    }
}
