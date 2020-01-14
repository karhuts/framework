<?php
declare(strict_types=1);
namespace Karthus\Logger;

/**
 * Class StdoutHandler
 *
 * @package Karthus\Logger
 */
class StdoutHandler implements LoggerHandlerInterface {
    /**
     * 处理日志
     * @param $level
     * @param $message
     */
    public function handle($level, $message) {
        // win系统普通打印
        if (!(stripos(PHP_OS, 'Darwin') !== false)
            && stripos(PHP_OS, 'WIN') !== false) {
            $message = preg_replace("/\\e\[[0-9]+m/", '', $message); // 过滤颜色
            echo $message;
            return;
        }
        // 带颜色打印
        echo $message;
    }
}
