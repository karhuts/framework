<?php
declare(strict_types=1);
namespace Karthus\Driver\Redis;

use Karthus\Exception\ConnectFail;
use Swoole\Coroutine\Redis as CoroutineRedis;

/**
 * 不做额外的封装
 *
 * Class RedisCluster
 *
 * @package Karthus\Driver\Redis
 */
class RedisCluster{

    /**
     * @var ClusterConfig
     */
    protected $config;

    /**
     * 节点客户端列表
     * @var $nodeClientList array
     */
    protected $nodeClientList = [];

    /**
     * @var CoroutineRedis;
     */
    protected $coroutineRedisClient;

    /**
     * @var string
     */
    protected $serverInfo;

    /**
     * RedisCluster constructor.
     *
     * @param ClusterConfig $clusterConfig
     */
    public function __construct(ClusterConfig $clusterConfig) {
        $this->config   = $clusterConfig;
    }

    /**
     * @return CoroutineRedis
     * @throws \Throwable
     */
    public function getCoroutineRedisClient(): CoroutineRedis {
        $this->connect();
        return $this->coroutineRedisClient;
    }

    /**
     * @throws \Throwable
     */
    public function connect() {
        $serverList     = $this->getServers();
        $idx            = array_rand($serverList);
        $this->serverInfo = $serverList[$idx];
        //然后开始连接
        if(isset($this->nodeClientList[$this->serverInfo])
            && $this->nodeClientList[$this->serverInfo] instanceof CoroutineRedis
            && $this->nodeClientList[$this->serverInfo]->connected){
            $this->coroutineRedisClient = $this->nodeClientList[$this->serverInfo];
        }else {
            try {
                $__         = explode(':', $this->serverInfo);
                $host       = $__[0];
                $port       = $__[1];

                $client     = new CoroutineRedis();
                $client->setOptions([
                    'compatibility_mode'    => true,
                ]);
                $ret        = $client->connect($host, (int) $port);

                if(!$ret){
                    $errno = $this->coroutineRedisClient->errCode;
                    $error = $this->coroutineRedisClient->errMsg;
                    throw new ConnectFail("connect to {$this->config->getHost()} at port {$this->config->getPort()} fail: {$errno} {$error}");
                }else {
                    $this->nodeClientList[$this->serverInfo] = $client;
                    $this->coroutineRedisClient              = $client;
                }
            }catch (\Throwable $exception){
                throw $exception;
            }
        }
    }

    /**
     * @return array
     */
    public function getServers(): array {
        return $this->config->getServerList();
    }

    /**
     * 断开连接
     */
    public function disconnect() {
        if($this->coroutineRedisClient->connected){
            $this->coroutineRedisClient->close();
            unset($this->nodeClientList[$this->serverInfo]);
        }
    }

    /**
     * 获取配置信息
     *
     * @return Config
     */
    public function getConfig() :ClusterConfig {
        return $this->config;
    }
}
