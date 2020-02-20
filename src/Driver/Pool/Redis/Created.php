<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Redis;

use Karthus\Driver\Pool\DynamicPool;
use Karthus\Driver\Redis\ClusterConfig;
use Karthus\Driver\Redis\Config;
use Karthus\Driver\Redis\RedisCluster;
use Karthus\Driver\Redis\Redis as RedisNode;

class Created extends DynamicPool {

    /***
     * Created constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config) {
        $func   = function() use ($config) {
            if($config instanceof ClusterConfig){
                $redis  = new RedisCluster($config);
            }else {
                $redis  = new RedisNode($config);
            }

            return $redis->getCoroutineRedisClient();
        };

        parent::__construct($func);
    }

}
