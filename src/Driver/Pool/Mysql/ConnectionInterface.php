<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Karthus\Driver\Pool\AbstractPool;

interface ConnectionInterface {
    function defer(float $timeout = null):? ClientInterface;
    function getClientPool(): AbstractPool;
    function getConfig():?Config;
}
