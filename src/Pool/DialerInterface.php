<?php
declare(strict_types=1);
namespace  Karthus\Pool;

interface DialerInterface {
    /**
     * 拨号
     * @return ConnectionTrait
     */
    public function dial();
}
