<?php
declare(strict_types=1);
namespace Karthus\Trigger;

interface TriggerInterface {
    /**
     * @param               $msg
     * @param int           $errorCode
     * @param Location|null $location
     * @return mixed
     */
    public function error($msg,int $errorCode = E_USER_ERROR, Location $location = null);

    /**
     * @param \Throwable $throwable
     * @return mixed
     */
    public function throwable(\Throwable $throwable);
}
