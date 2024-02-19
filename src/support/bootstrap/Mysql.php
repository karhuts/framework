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

namespace karthus\support\bootstrap;

use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Database\Capsule\Manager;
use karthus\Bootstrap;

use function karthus\config;

class Mysql implements Bootstrap
{
    protected static bool $enable = false;
    public static function run(): void
    {
        if (! class_exists(Manager::class)) {
            return;
        }

        $config = config('database', []);
        $connections = $config['connections'] ?? [];
        if (empty($connections)) {
            return;
        }
        $manager = new Manager(IlluminateContainer::getInstance());

        $default = $config['default'] ?? false;
        if ($default) {
            $defaultConfig = $connections[$config['default']];
            $manager->addConnection($defaultConfig);
        }

        foreach ($connections as $name => $config) {
            $manager->addConnection($config, $name);
        }

        $manager->setAsGlobal();
        $manager->bootEloquent();

        $enable_log = config('database.enable_log');
        static::$enable = (bool) $enable_log;
        if ($enable_log) {
            $manager->getConnection()->enableQueryLog();
        }
    }

    /**
     * @return array
     */
    public static function getQueries(): array
    {
        if (static::$enable === false) {
            return [];
        }
        return Manager::connection()->getQueryLog();
    }
}
