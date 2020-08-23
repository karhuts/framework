<?php
declare(strict_types=1);
namespace Karthus\Driver\Mysqli;

use Karthus\Exception\ConnectFail;
use Swoole\Coroutine\MySQL as CoroutineMySQL;

/**
 * 不进行额外多余的封装，
 * 请看swoole文档
 * 由于进程池的特殊性，我只封装了事务相关的接口
 *
 * Class Mysqli
 *
 * @package Karthus\Driver\Mysqli
 */
class Mysqli {
    /**
     * @var Config
     */
    private $config;//数据库配置项
    private $coroutineMysqlClient;//swoole 协程MYSQL客户端
    private $currentReconnectTimes = 0;

    public function __construct(Config $config) {
        $this->config               = $config;
        $this->coroutineMysqlClient = new CoroutineMySQL();
    }

    /**
     * 链接数据库
     * @return true 链接成功返回 true
     * @throws \Throwable|ConnectFail 链接失败时请外部捕获该异常进行处理
     */
    public function connect() {
        if ($this->coroutineMysqlClient->connected) {
            return true;
        } else {
            try {
                $config     = $this->config->toArray();
                //如果 HOST 是个数组
                $__         = $config['host'];
                unset($config['host']);
                if(is_array($__)){
                    $idx    = array_rand($__);
                    $host   = $__[$idx];
                }else {
                    $host           = $__;
                }
                // $config['host'] 是一个字符串
                list($host, $port)  = explode(':', $host);
                $port       = $port === null ? $config['port'] : intval($port);
                $config['port']     = $port;
                $config['host']     = $host;
                $ret = $this->coroutineMysqlClient->connect($config);
                if ($ret) {
                    $this->currentReconnectTimes = 0;
                    return true;
                } else {
                    $errno = $this->coroutineMysqlClient->connect_errno;
                    $error = $this->coroutineMysqlClient->connect_error;
                    if($this->config->getMaxReconnectTimes() > $this->currentReconnectTimes){
                        $this->currentReconnectTimes++;
                        return $this->connect();
                    }
                    throw new ConnectFail("connect to {$this->config->getUser()}@{$host} at port {$port} fail: {$errno} {$error}");
                }
            } catch (\Throwable $throwable) {
                throw $throwable;
            }
        }
    }

    /**
     * 断开数据库链接
     */
    public function disconnect() {
        $this->coroutineMysqlClient->close();
    }

    /**
     * 选择数据库
     *
     * @param string $dbName
     * @param float  $timeout
     * @return mixed
     * @throws \Throwable
     */
    public function selectDb(string $dbName,float $timeout = 1.0) {
        return $this->getMysqlClient()->query('use '.$dbName, $timeout);
    }

    /**
     * 获取协程客户端
     *
     * @return CoroutineMySQL
     * @throws \Throwable
     */
    public function getMysqlClient(): CoroutineMySQL {
        /*
         * 确保已经连接
         */
        $this->connect();
        return $this->coroutineMysqlClient;
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
     * 关闭
     *
     * @return bool
     */
    public function close():bool {
        if (isset($this->coroutineMysqlClient) && $this->coroutineMysqlClient->connected) {
            $this->coroutineMysqlClient->close();
        }
        unset($this->coroutineMysqlClient);
        return true;
    }

    /**
     * 析构被调用时关闭当前链接并释放客户端对象
     */
    public function __destruct() {
        $this->close();
    }
}
