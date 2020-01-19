<?php
declare(strict_types=1);
namespace Karthus\Http\Router;

use FastRoute\RouteCollector;
use Karthus\Config;
use Karthus\Http\AbstractInterface\AbstractRouter;

class Router extends AbstractRouter{

    /**
     * 初始化路由
     *
     * @param RouteCollector $routeCollector
     */
    public function initialize(RouteCollector $routeCollector) {
        $routers    = Config::getInstance()->getConf('ROUTERS');
        $routeCollector->addRoute();
    }
}
