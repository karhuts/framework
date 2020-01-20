<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Redis;

use Karthus\Component\Singleton;
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

        $class      = "Karthus\\Driver\\Pool\\Mysql\\Created";
        $poolConfig = PoolManager::getInstance()->register($class);
        $poolConfig->setExtraConf($config);

        $this->container[$poolName] = [
            'class'  => $class,
            'config' => $config
        ];

        return $poolConfig;
    }

}
