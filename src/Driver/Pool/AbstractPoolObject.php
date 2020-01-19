<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool;

abstract class AbstractPoolObject implements PoolObjectInterface {
    /**
     * @return mixed|void
     */
    public function gc() {
        $list = get_class_vars(static::class);
        foreach ($list as $property => $value){
            $this->$property = $value;
        }
    }

    /**
     * @return bool
     */
    public function beforeUse():bool {
        return true;
    }

    public function objectRestore() {}
}
