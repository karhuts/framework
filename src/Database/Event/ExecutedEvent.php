<?php
declare(strict_types=1);
namespace Karthus\Database\Event;

class ExecutedEvent {
    /**
     * @var string
     */
    public $sql = '';

    /**
     * @var float
     */
    public $time = 0;
}
