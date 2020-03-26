<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool;

class DynamicPool extends AbstractPool {

    protected $func;

    /**
     * DynamicPool constructor.
     *
     * @param PoolConf $conf
     * @param callable $func
     */
    public function __construct(callable $func, PoolConf $conf = null) {
        $this->func = $func;
        if($conf === null){
            $conf   = new PoolConf();
        }
        parent::__construct($conf);
    }

    protected function createObject() {
    }
}
