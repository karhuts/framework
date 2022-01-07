<?php
declare(strict_types=1);

namespace Karthus;

use Exception;
use RuntimeException;

class Listener {
    private static $instance;

    private static $config;

    private function __construct(){}

    /**
     * @return Listener
     */
    public static function getInstance(): Listener {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$config = Config::getInstance()->get('listeners');
        }
        return self::$instance;
    }

    /**
     * @throws Exception
     */
    public function listen($listener, ...$args): void {
        $listeners = self::$config[$listener] ?? [];
        while ($listeners) {
            [$class, $func] = array_shift($listeners);
            try {
                $class::getInstance()->{$func}(...$args);
            } catch (Exception $e) {
                throw new RuntimeException($e->getMessage());
            }
        }
    }
}