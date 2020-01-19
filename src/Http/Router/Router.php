<?php
declare(strict_types=1);
namespace Karthus\Http\Router;

use FastRoute\RouteCollector;
use Karthus\Component\Singleton;
use Karthus\Config;
use Karthus\Http\AbstractInterface\AbstractRouter;

class Router extends AbstractRouter{
    use Singleton;
    /**
     * @var array
     */
    private $routers;


    /**
     * 设置路由
     *
     * @param array $routers
     */
    public function setRouters(array $routers = []){
        $this->routers = $routers;
    }

    /**
     * 初始化路由
     *
     * @param RouteCollector $routeCollector
     */
    public function initialize(RouteCollector $routeCollector) {
        $routers    = $this->routers;
        print_r($routers);
    }
}
