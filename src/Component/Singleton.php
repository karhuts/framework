<?php
declare(strict_types=1);
namespace Karthus\Component;

trait Singleton {
    /**
     * @var static
     */
    private static $instance;

    /**
     * @param mixed ...$args
     * @return static
     */
    public static function getInstance(...$args): Singleton{
        if(!isset(self::$instance)){
            self::$instance = new static(...$args);
        }
        return self::$instance;
    }
}
