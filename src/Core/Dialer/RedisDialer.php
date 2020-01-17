<?php
declare(strict_types=1);
namespace Karthus\Core\Dialer;

use Karthus\Pool\ConnectionTrait;
use Karthus\Pool\DialerInterface;

/**
 * Redis 拨号器
 *
 * Class RedisDialer
 *
 * @package Karthus\Core\Dialers
 */
class RedisDialer implements DialerInterface {

    /**
     * @inheritDoc
     */
    public function dial() {
    }
}
