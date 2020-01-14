<?php
declare(strict_types=1);
namespace Karthus\Logger;

/**
 * Interface LoggerHandlerInterface
 *
 * @package Karthus\Logger
 */
interface LoggerHandlerInterface {
    /**
     * 处理日志
     * @param $level
     * @param $message
     * @return void
     */
    public function handle($level, $message);
}
