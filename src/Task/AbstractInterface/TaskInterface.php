<?php
declare(strict_types=1);
namespace Karthus\Task\AbstractInterface;

interface TaskInterface {
    /**
     * @param int $taskId
     * @param int $workerIndex
     * @return mixed
     */
    public function run(int $taskId,int $workerIndex);

    /**
     * @param \Throwable $throwable
     * @param int        $taskId
     * @param int        $workerIndex
     * @return mixed
     */
    public function onException(\Throwable $throwable,int $taskId,int $workerIndex);
}
