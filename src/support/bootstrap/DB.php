<?php
declare(strict_types=1);

namespace karthus\support\bootstrap;

use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Database\Capsule\Manager;
use karthus\Bootstrap;

class DB implements Bootstrap {

    /**
     * @return void
     */
    public static function run(): void
    {
        if (!class_exists(Manager::class)) {
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

        /// TODO db-paginator????
    }

}
