<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Redis;

use Karthus\Component\Singleton;
use Karthus\Driver\Pool\AbstractPool;
use Karthus\Driver\Pool\PoolConf;
use Karthus\Driver\Pool\PoolManager;
use Karthus\Driver\Redis\Config;
use Karthus\Exception\PoolException;

class Redis {
    use Singleton;
    /**
     * @var array
     */
    private $container = [];

    /**
     * 注册
     *
     * @param string $poolName
     * @param Config $config
     * @return PoolConf
     * @throws \ReflectionException
     */
    public function register(string $poolName, Config $config): PoolConf{
        if(isset($this->container[$poolName])){
            //已经注册，则抛出异常
            throw new PoolException("redis pool:{$poolName} is already been register");
        }

        $poolConfig = PoolManager::getInstance()->register(Created::class, $poolName);
        $poolConfig->setExtraConf($config);

        $this->container[$poolName] = [
            'class'  => Created::class,
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
    public static function defer(string $name, $timeout = null): ?Connection {
        $name   = strtolower($name);
        $pool   = static::getInstance()->pool($name);
        if ($pool) {
            return $pool::defer($name, $timeout);
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
            return $pool::invoke($name, $call, $timeout);
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
                $pool   = PoolManager::getInstance()->getPool($class, $name);
                $this->container[$name] = $pool;
                return $this->pool($name);
            }
        } else {
            return null;
        }
    }
}
