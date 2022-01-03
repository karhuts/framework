<?php
declare(strict_types=1);

namespace Karthus;

trait Singleton {
    private static $instance;

    /**
     * @param ...$args
     * @return static
     */
    public static function getInstance(...$args) {
        if (!isset(self::$instance)) {
            self::$instance = new static(...$args);
        }
        return self::$instance;
    }
}
