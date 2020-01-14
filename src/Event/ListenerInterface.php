<?php
declare(strict_types=1);
namespace Karthus\Event;

/**
 * Interface ListenerInterface
 *
 * @package Karthus\Event
 */
interface ListenerInterface {
    /**
     * 监听的事件
     * @return array
     */
    public function events(): array;
    /**
     * 处理事件
     * @param object $event
     */
    public function process($event);
}
