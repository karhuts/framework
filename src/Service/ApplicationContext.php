<?php
namespace Karthus\Service;

use Psr\Container\ContainerInterface;

class ApplicationContext {
    /**
     * @var ContainerInterface
     */
    private static $container;

    public static function getContainer(): ContainerInterface {
        return self::$container;
    }

    public static function hasContainer(): bool {
        return isset(self::$container);
    }

    public static function setContainer(ContainerInterface $container): ContainerInterface {
        self::$container = $container;
        return $container;
    }
}
