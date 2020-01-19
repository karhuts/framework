<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Karthus\Driver\Mysqli\Mysqli;
use Karthus\Driver\Pool\PoolObjectInterface;

class Connection extends Mysqli implements PoolObjectInterface{

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function gc() {
        try{
            $this->rollback();
        }catch (\Throwable $throwable){
            trigger_error($throwable->getMessage());
        }
        $this->getMysqlClient()->close();
    }

    /**
     * @inheritDoc
     */
    public function objectRestore() {
        try{
            $this->rollback();
        }catch (\Throwable $throwable){
            trigger_error($throwable->getMessage());
        }
    }

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function beforeUse(): bool {
        return $this->getMysqlClient()->connected;
    }
}
