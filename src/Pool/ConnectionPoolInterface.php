<?php
declare(strict_types=1);
namespace Karthus\Pool;

interface ConnectionPoolInterface {
    /**
     * 获取连接
     * @return mixed
     */
    public function getConnection();
    /**
     * 释放连接
     * @param $connection
     */
    public function release($connection);
    /**
     * 丢弃连接
     * @param $connection
     * @return bool
     */
    public function discard($connection);
    /**
     * 获取连接池的统计信息
     * @return array
     */
    public function getStats();
}
