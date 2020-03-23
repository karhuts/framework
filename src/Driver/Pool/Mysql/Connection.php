<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Karthus\Driver\Pool\AbstractPool;

class Connection implements ConnectionInterface {
    /** @var Config */
    protected $config;

    /** @var AbstractPool */
    protected $pool;

    /**
     * Connection constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * @param float|null $timeout
     * @return ClientInterface|null
     * @throws \Throwable
     */
    public function defer(float $timeout = null): ? ClientInterface {
        if ($timeout === null) {
            $timeout = $this->config->getGetObjectTimeout();
        }
        return $this->getPool()->defer($timeout);
    }

    /**
     * @return AbstractPool
     */
    public function getClientPool(): AbstractPool {
        return $this->getPool();
    }

    /**
     * @return MysqlPool
     */
    protected function getPool(): MysqlPool {
        if (!$this->pool) {
            $this->pool = new MysqlPool($this->config);
        }
        return $this->pool;
    }

    /**
     * @return Config|null
     */
    public function getConfig(): ?Config {
        return $this->config;
    }
}
