<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Redis;

use Karthus\Driver\Pool\PoolObjectInterface;
use Karthus\Driver\Redis\Redis as DefaultRedis;

class Connection extends DefaultRedis implements PoolObjectInterface {

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function gc() {}

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function objectRestore() {}

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function beforeUse(): bool {
        return !!$this->getRedisClient()->ping();
    }
}
