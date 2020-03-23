<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Karthus\Driver\Mysqli\Config as MysqlConfig;
use Karthus\Driver\Pool\AbstractPool;
use Karthus\Exception\Exception;

class MysqlPool extends AbstractPool {

    /**
     * @return MysqliClient
     * @throws \ReflectionException
     * @throws \Throwable
     */
    protected function createObject() {
        /** @var Config $config */
        $config = $this->getConfig();
        $client = new MysqliClient(new MysqlConfig($config->toArray()));
        if($client->connect()){
            return $client;
        }else{
            throw new Exception($client->getMysqlClient()->connect_error);
        }
    }
}
