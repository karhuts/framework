<?php
declare(strict_types=1);
namespace Karthus\Pool;

/**
 * Trait ConnectionTrait
 *
 * @package Karthus\Pool
 */
trait ConnectionTrait {
    /**
     * @var ConnectionPoolInterface
     */
    public $pool;
    /**
     * 丢弃连接
     * @return bool
     */
    public function discard() {
        if (isset($this->pool)) {
            return $this->pool->discard($this);
        }
        return false;
    }
    /**
     * 释放连接
     * @return bool
     */
    public function release() {
        if (isset($this->pool)) {
            return $this->pool->release($this);
        }
        return false;
    }
}
