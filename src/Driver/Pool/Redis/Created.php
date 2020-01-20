<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Redis;

use Karthus\Driver\Pool\AbstractPool;

class Created extends AbstractPool {

    protected function createObject() {
        return new Connection($this->getConfig()->getExtraConf());
    }
}
