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

use Closure;
use karthus\route\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouteList extends Command
{
    protected static $defaultName = 'route:list';

    protected static $defaultDescription = 'Route list';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $headers = ['uri', 'method', 'callback', 'middleware', 'name'];
        $rows = [];
        // todo 加载路由配置信息
        foreach (Router::getRoutes() as $route) {
            foreach ($route->getMethods() as $method) {
                $cb = $route->getCallback();
                $cb = $cb instanceof Closure ? 'Closure' : (is_array($cb) ? json_encode($cb) : var_export($cb, true));
                $rows[] = [$route->getPath(), $method, $cb, json_encode($route->getMiddleware() ?: null), $route->getName()];
            }
        }

        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        return self::SUCCESS;
    }
}
