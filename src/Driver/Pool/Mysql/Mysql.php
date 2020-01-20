<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Karthus\Component\Singleton;
use Karthus\Driver\Mysqli\Config;
use Karthus\Driver\Pool\AbstractPool;
use Karthus\Driver\Pool\PoolConf;
use Karthus\Driver\Pool\PoolManager;
use Karthus\Exception\MysqlPoolException;

class Mysql {
    use Singleton;
    private $container = [];

    /**
     * @param string $poolName
     * @param Config $config
     * @return PoolConf
     * @throws \ReflectionException
     */
    public function register(string $poolName, Config $config) : PoolConf {
        if (isset($this->container[$poolName])) {
            //已经注册，则抛出异常
            throw new MysqlPoolException("mysqlPool:{$poolName} is already been register");
        }

        $class      = "Karthus\\Driver\\Pool\\Redis\\Created";
        $poolConfig = PoolManager::getInstance()->register($class);
        $poolConfig->setExtraConf($config);

        $this->container[$poolName] = [
            'class'  => $class,
            'config' => $config
        ];
        return $poolConfig;
    }

    /**
     * @param string $name
     * @param null   $timeout
     * @return Connection|null
     * @throws \Throwable
     */
    static function defer(string $name, $timeout = null): ?Connection {
        $pool = static::getInstance()->pool($name);
        if ($pool) {
            return $pool::defer($timeout);
        } else {
            return null;
        }
    }

    /**
     * @param string     $name
     * @param callable   $call
     * @param float|null $timeout
     * @return |null
     * @throws \Throwable
     */
    static function invoker(string $name, callable $call, float $timeout = null) {
        $pool = static::getInstance()->pool($name);
        if ($pool) {
            return $pool::invoke($call, $timeout);
        } else {
            return null;
        }
    }

    /**
     * @param string $name
     * @return AbstractPool|null
     */
    public function pool(string $name): ?AbstractPool {
        if (isset($this->container[$name])) {
            $item = $this->container[$name];
            if ($item instanceof AbstractPool) {
                return $item;
            } else {

                $class  = $item['class'];
                $pool   = PoolManager::getInstance()->getPool($class);
                $this->container[$name] = $pool;
                return $this->pool($name);
            }
        } else {
            return null;
        }
    }

}
