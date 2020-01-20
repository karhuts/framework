<?php
declare(strict_types=1);
namespace Karthus\Driver\Redis;

use Karthus\Exception\ConnectFail;
use Swoole\Coroutine\Redis as CoroutineRedis;

/**
 * 不做额外的封装
 *
 * Class Redis
 *
 * @package Karthus\Driver\Redis
 */
class Redis {
    /**
     * Redis配置选项
     *
     * @var Config
     */
    private $config;

    /**
     * @var CoroutineRedis
     */
    private $coroutineRedisClient;

    /**
     * Redis constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->config               = $config;
        $this->coroutineRedisClient = new CoroutineRedis();
    }

    /**
     *  * 链接数据库
     *
     * @return bool 链接成功返回 true
     * @throws \Throwable
     */
    public function connect(): bool {
        if($this->coroutineRedisClient->connected){
            return true;
        }else {
            try {
                $ret    = $this->coroutineRedisClient->connect(
                    $this->config->getHost(),
                    $this->config->getPort(),
                    $this->config->getSerialize()
                );

                if($ret){
                    return true;
                }else {
                    $errno = $this->coroutineRedisClient->errCode;
                    $error = $this->coroutineRedisClient->errMsg;

                    throw new ConnectFail("connect to {$this->config->getHost()} at port {$this->config->getPort()} fail: {$errno} {$error}");
                }
            }catch (\Throwable $exception){
                throw $exception;
            }
        }
    }

    /**
     * 断开连接
     */
    public function disconnect() {
        if($this->coroutineRedisClient->connected){
            $this->coroutineRedisClient->close();
        }
    }

    /**
     * @return CoroutineRedis
     * @throws \Throwable
     */
    public function getCoroutineRedisClient() :CoroutineRedis{
        //确保连接
        $this->connect();
        return $this->coroutineRedisClient;
    }

    /**
     * 获取配置信息
     *
     * @return Config
     */
    public function getConfig() :Config {
        return $this->config;
    }

    /**
     * 析构被调用时关闭当前链接并释放客户端对象
     */
    public function __destruct() {
        if (isset($this->coroutineRedisClient) && $this->coroutineRedisClient->connected) {
            $this->coroutineRedisClient->close();
        }
        unset($this->coroutineRedisClient);
    }
}
