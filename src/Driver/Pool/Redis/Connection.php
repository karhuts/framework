<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Redis;

use Karthus\Driver\Pool\PoolObjectInterface;
use Karthus\Driver\Redis\Redis;

class Connection extends Redis implements PoolObjectInterface {

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function gc() {
        $this->getCoroutineRedisClient()->close();
    }

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function objectRestore() {
        $this->getCoroutineRedisClient()->close();
    }

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function beforeUse(): bool {
        $this->getCoroutineRedisClient()->connected;
    }
}
