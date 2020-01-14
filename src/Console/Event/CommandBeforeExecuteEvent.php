<?php
declare(strict_types=1);
namespace Karthus\Console\Event;

/**
 * Class CommandBeforeExecuteEvent
 */
class CommandBeforeExecuteEvent {
    /**
     * @var string
     */
    public $command;
    /**
     * CommandBeforeExecuteEvent constructor.
     * @param string $command
     */
    public function __construct(string $command) {
        $this->command = $command;
    }
}
