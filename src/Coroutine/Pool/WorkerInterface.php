<?php
declare(strict_types=1);

namespace Karthus\Coroutine\Pool;

interface WorkerInterface {
    /**
     * 启动
     */
    public function start();
    /**
     * 停止
     */
    public function stop();
    /**
     * 处理
     * @param $data
     */
    public function handle($data);
}
