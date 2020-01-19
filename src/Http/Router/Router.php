<?php
declare(strict_types=1);
namespace Karthus\Http\Router;

use FastRoute\RouteCollector;
use Karthus\Component\Singleton;
use Karthus\Core;
use Karthus\Http\AbstractInterface\AbstractRouter;

class Router extends AbstractRouter{
    use Singleton;
    /**
     * @var array
     */
    private $routers;

    /**
     * 初始化路由
     *
     * @param RouteCollector $routeCollector
     */
    public function initialize(RouteCollector $routeCollector) {
        $routers    = Core::getInstance()->loadRouter();
        print_r($routers);
    }
}
