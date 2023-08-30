<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  min@bluecity.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\support\bootstrap;

use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Database\Capsule\Manager;
use karthus\Bootstrap;

use function karthus\config;

class Mysql implements Bootstrap
{
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

        // / TODO db-paginator????
    }
}
