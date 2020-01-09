<?php
namespace Karthus\Contract;

interface PoolInterface {

    /**
     * Get a connection from the connection pool.
     */

    public function get(): ConnectionInterface;

    /**
     * Release a connection back to the connection pool.
     */
    public function release(ConnectionInterface $connection): void;

    /**
     * Close and clear the connection pool.
     */
    public function flush(): void;
}
