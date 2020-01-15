<?php
declare(strict_types=1);
namespace Karthus\Pool\Event;

class ConnectionDiscardedEvent {
    /**
     * @var object
     */
    public $connection;

    /**
     * ConnectionDiscardEvent constructor.
     *
     * @param object $connection
     */
    public function __construct(object $connection) {
        $this->connection = $connection;
    }
}
