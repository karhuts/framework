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

namespace karthus\command;

use karthus\cache\FileCache;
use karthus\route\Router;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function karthus\config;
use function karthus\config_path;
use function karthus\console_info;
use function karthus\runtime_path;
use function Opis\Closure\serialize;

class RouteCache extends Command
{
    protected static $defaultName = 'route:cache';

    protected static $defaultDescription = 'Create a route cache file for faster route registration';

    /**
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = config('app.router_cache_file', runtime_path('router/karthus.cache'));
        // 加载路由信息
        $cache = new FileCache($path);
        $paths = [config_path()];
        Router::load($paths);
        $router = Router::getRoutes();
        $cache->set(\karthus\route\Cache\Router::CACHE_KEY, @serialize($router));

        console_info($output, 'Routes cached successfully. ');
        return self::SUCCESS;
    }
}
