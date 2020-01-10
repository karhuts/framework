<?php
declare(strict_types=1);

namespace Karthus\Router;

class Handler {
    /**
     * @var array|callable|string
     */
    public $callback;
    /**
     * @var string
     */
    public $route;

    /**
     * Handler constructor.
     *
     * @param        $callback
     * @param string $route
     */
    public function __construct($callback, string $route) {
        $this->callback = $callback;
        $this->route = $route;
    }
}
