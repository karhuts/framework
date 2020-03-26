<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Karthus\Component\Singleton;
use Karthus\Driver\Pool\AbstractPool;
use Karthus\Driver\Pool\PoolConf;
use Karthus\Driver\Pool\PoolManager;
use Karthus\Exception\Exception;
use Karthus\Exception\PoolException;
use Swoole\Coroutine;

class Manager {
    use Singleton;

    /**
     * 连接信息
     *
     * @var array
     */
    protected $connections   = [];
    /**
     * @var array
     */
    protected $connectorPool = [];

    /**
     * 注册
     *
     * @param ConnectionInterface $connection
     * @param string              $connectionName
     * @return PoolConf
     * @throws \ReflectionException
     */
    public function register(ConnectionInterface $connection, string $connectionName = 'default'): PoolConf{
        if(isset($this->connections[$connectionName])){
            //已经注册，则抛出异常
            throw new PoolException("Mysql pool:{$connectionName} is already been register");
        }

        $this->connections[$connectionName]     = $connection;
        $this->connectorPool[$connectionName]   = $connection;

        $poolConfig = PoolManager::getInstance()->register(MysqlPool::class, $connectionName);
        $poolConfig->setExtraConf($connection->getConfig());

        return $poolConfig;
    }

    /**
     * 获取连接
     *
     * @param string $connectionName
     * @return ConnectionInterface|null
     */
    public function getConnection(string $connectionName = 'default'):?ConnectionInterface {
        if(isset($this->connections[$connectionName])){
            return $this->connections[$connectionName];
        }
        return null;
    }

    /**
     * @param string $builder
     * @param string|ClientInterface $connection
     * @param float|null $timeout
     * @return array|null
     * @throws Exception
     * @throws \Throwable
     */
    public function query(string $builder, $connection = 'default', float $timeout = null): Result {
        if(is_string($connection)){
            $_connection = $this->getConnection($connection);
            if(!$_connection){
                throw new Exception("connection : {$connection} not register");
            }
            $client = self::getInstance()->defer($connection, $timeout);
            if(empty($client)){
                throw new Exception("connection : {$connection} is empty");
            }
        }else{
            $client = $connection;
        }

        $ret        = $client->query($builder);
        return $ret;
    }

    /**
     * @param string     $connection
     * @param float|null $timeout
     * @return bool
     * @throws \Throwable
     */
    public function transaction($connection = 'default', float $timeout = null): bool{
        if(is_string($connection)){
            $_connection = $this->getConnection($connection);
            if(!$_connection){
                throw new Exception("connection : {$connection} not register");
            }
            $client = self::getInstance()->defer($connection, $timeout);
            if(empty($client)){
                throw new Exception("connection : {$connection} is empty");
            }
        }else{
            $client = $connection;
        }

        return $client->begin();
    }

    /**
     * 提交事务
     *
     * @param string     $connection
     * @param float|null $timeout
     * @return bool
     * @throws \Throwable
     */
    public function commit($connection = 'default', float $timeout = null): bool {
        if(is_string($connection)){
            $_connection = $this->getConnection($connection);
            if(!$_connection){
                throw new Exception("connection : {$connection} not register");
            }
            $client = self::getInstance()->defer($connection, $timeout);
            if(empty($client)){
                throw new Exception("connection : {$connection} is empty");
            }
        }else{
            $client = $connection;
        }

        return $client->commit();
    }

    /**
     * 回滚
     *
     * @param string     $connection
     * @param float|null $timeout
     * @return bool
     * @throws \Throwable
     */
    public function rollback($connection = 'default', float $timeout = null): bool {
        if(is_string($connection)){
            $_connection = $this->getConnection($connection);
            if(!$_connection){
                throw new Exception("connection : {$connection} not register");
            }
            $client = self::getInstance()->defer($connection, $timeout);
            if(empty($client)){
                throw new Exception("connection : {$connection} is empty");
            }
        }else{
            $client = $connection;
        }

        return $client->rollback();
    }

    /**
     * MYSQL 转义
     *
     * @param string     $str
     * @param string     $connection
     * @param float|null $timeout
     * @return string
     * @throws \Throwable
     */
    public function escape(string $str, $connection = 'default', float $timeout = null) : string {
        if(is_string($connection)){
            $_connection = $this->getConnection($connection);
            if(!$_connection){
                throw new Exception("connection : {$connection} not register");
            }
            $client = self::getInstance()->defer($connection, $timeout);
            if(empty($client)){
                throw new Exception("connection : {$connection} is empty");
            }
        }else{
            $client = $connection;
        }

        return $client->escape($str);
    }

    /**
     * @param string $name
     * @param null   $timeout
     * @return MysqliClient|null
     * @throws \Throwable
     */
    public static function defer(string $name, $timeout = null): ?MysqliClient {
        $pool = static::getInstance()->pool($name);
        if ($pool) {
            return $pool::defer($name, $timeout);
        } else {
            return null;
        }
    }

    /**
     * @param string $name
     * @return AbstractPool|null
     */
    public function pool(string $name): ?AbstractPool {
        if (isset($this->connectorPool[$name])) {
            $item = $this->connectorPool[$name];
            if ($item instanceof AbstractPool) {
                return $item;
            } else {
                $pool   = PoolManager::getInstance()->getPool(MysqlPool::class, $name);
                $this->connectorPool[$name]   = $pool;
                return $this->pool($name);
            }
        } else {
            return null;
        }
    }

    /**
     * 引用
     *
     * @param callable   $call
     * @param string     $connectionName
     * @param float|null $timeout
     * @return mixed
     * @throws \Throwable
     */
    public function invoke(callable $call,string $connectionName = 'default',float $timeout = null) {
        $pool = static::getInstance()->pool($connectionName);
        if ($pool) {
            return $pool::invoke($connectionName, $call, $timeout);
        } else {
            return null;
        }
    }
}
