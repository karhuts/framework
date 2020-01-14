<?php
declare(strict_types=1);
namespace Karthus\Logger;

/**
 * Class MultiHandler
 *
 * @package Karthus\Logger
 */
class MultiHandler implements LoggerHandlerInterface {
    /**
     * 日志处理器集合
     * @var LoggerHandlerInterface[]
     */
    public $handlers = [];
    /**
     * MultiHandler constructor.
     * @param LoggerHandlerInterface ...$handlers
     */
    public function __construct(LoggerHandlerInterface ...$handlers) {
        $this->handlers = $handlers;
    }
    /**
     * 新增
     * @param LoggerHandlerInterface $handler
     */
    public function add(LoggerHandlerInterface $handler) {
        $this->handlers[] = $handler;
    }
    /**
     * 处理日志
     * @param $level
     * @param $message
     */
    public function handle($level, $message) {
        foreach ($this->handlers as $handler) {
            /** @var LoggerHandlerInterface $handler */
            $handler->handle($level, $message);
        }
    }
}
