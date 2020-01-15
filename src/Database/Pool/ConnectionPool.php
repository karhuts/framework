<?php
declare(strict_types=1);
namespace Karthus\Database\Pool;

use Karthus\Pool\AbstractConnectionPool;
use Karthus\Pool\ConnectionPoolInterface;

class ConnectionPool extends AbstractConnectionPool implements ConnectionPoolInterface {
    /**
     * 获取连接
     * @return Connection
     */
    public function getConnection() {
        return parent::getConnection();
    }
}
