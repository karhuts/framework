<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool;

interface PoolObjectInterface {
    /**
     * UNSET
     *
     * @return mixed
     */
    public function gc();

    /**
     * 销毁
     *
     * @return mixed
     */
    public function objectRestore();

    /**
     * 使用前调用
     *
     * @return bool
     */
    public function beforeUse():bool;
}
