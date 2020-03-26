<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Redis;

use Karthus\Driver\Pool\AbstractPool;
use Karthus\Driver\Redis\ClusterConfig;
use Karthus\Driver\Redis\Config;

class Created extends AbstractPool {

    /**
     * @return Connection
     */
    protected function createObject(): Connection {
        $config     = $this->getConfig()->getExtraConf();
        if($config instanceof ClusterConfig){
            return new Connection($this->getConfig()->getExtraConf());
        }
    }
}
