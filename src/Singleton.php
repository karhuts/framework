<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  294953530@qq.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus;

trait Singleton
{
    /**
     * @var static
     */
    private static $instance;

    /**
     * @param mixed ...$args
     */
    public static function getInstance(...$args): static
    {
        if (! isset(self::$instance)) {
            self::$instance = new static(...$args);
        }
        return self::$instance;
    }
}
