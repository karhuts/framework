<?php
declare(strict_types=1);

namespace Karthus\Core;

use Karthus\Database\Connection;
use Karthus\Pool\DialerInterface;

/**
 * Class DatabaseDialer
 *
 * @package Karthus\Core
 */
class DatabaseDialer implements DialerInterface {

    /**
     * 拨号
     * @return Connection
     */
    public function dial() {
        // 创建一个连接并返回
        /** @var  $context Connection */
        $context    = context()->get(Connection::class);
        return $context;
    }
}
