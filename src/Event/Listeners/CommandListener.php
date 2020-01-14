<?php
declare(strict_types=1);
namespace Karthus\Event\Listeners;

use Karthus\Console\CommandLine\Flag;
use Karthus\Console\Event\CommandBeforeExecuteEvent;
use Karthus\Core\Process;
use Karthus\Core\Run;
use Karthus\Event\ListenerInterface;
use Swoole\Exception;

class CommandListener implements ListenerInterface {
    /**
     * 监听的事件
     * @return array
     */
    public function events(): array {
        // 要监听的事件数组，可监听多个事件
        return [
            CommandBeforeExecuteEvent::class,
        ];
    }

    /**
     * 处理事件
     *
     * @param object $event
     * @throws Exception
     */
    public function process($event) {
        // 事件触发后，会执行该方法
        // 守护处理
        if ($event instanceof CommandBeforeExecuteEvent) {
            switch ($event->command) {
                case Run::class:
                    if (Flag::bool(['d', 'daemon'], false)) {
                        Process::daemon();
                    }
                    break;
            }
        }
    }
}
