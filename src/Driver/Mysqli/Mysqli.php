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

    /**
     * 事务配置项
     */
    private $startTransaction = false;
    private $transactionLevel = 0;  // 当前的事务层级

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
                $ret = $this->coroutineMysqlClient->connect($this->config->toArray());
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
                    throw new ConnectFail("connect to {$this->config->getUser()}@{$this->config->getHost()} at port {$this->config->getPort()} fail: {$errno} {$error}");
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
        return $this->getMysqlClient()->query('use '.$dbName,$timeout);
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
     * 析构被调用时关闭当前链接并释放客户端对象
     */
    public function __destruct() {
        if (isset($this->coroutineMysqlClient) && $this->coroutineMysqlClient->connected) {
            $this->coroutineMysqlClient->close();
        }
        unset($this->coroutineMysqlClient);
    }

    /**
     * 开启事务
     *
     * @return bool 是否成功开启事务
     * @throws ConnectFail*@throws \Throwable
     * @throws \Throwable
     */
    public function startTransaction(): bool {
        if ($this->startTransaction) {
            return true;
        } else {
            $this->connect();
            $res = $this->coroutineMysqlClient->query('start transaction');
            if ($res) {
                $this->startTransaction = true;
            }
            return $res;
        }
    }

    /**
     * 提交事务
     *
     * @return bool 是否成功提交事务
     * @throws ConnectFail*@throws \Throwable
     * @throws \Throwable
     */
    public function commit(): bool {
        if ($this->startTransaction) {
            $this->connect();
            $res = $this->coroutineMysqlClient->query('commit');
            if ($res) {
                $this->startTransaction = false;
            }
            return $res;
        } else {
            return true;
        }
    }

    /**
     * 回滚事务
     *
     * @param bool $commit
     * @return array|bool
     * @throws ConnectFail*@throws \Throwable
     * @throws \Throwable
     */
    public function rollback($commit = true) {
        if ($this->startTransaction) {
            $this->connect();
            $res = $this->coroutineMysqlClient->query('rollback');
            if ($res && $commit) {
                $res = $this->commit();
                if ($res) {
                    $this->startTransaction = false;
                }
                return $res;
            } else {
                return $res;
            }
        } else {
            return true;
        }
    }

}
