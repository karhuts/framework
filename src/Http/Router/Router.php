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
        $routers    = Core::getInstance()->getRouters();
        if($routers){
            //开始进行路由初始化了
            foreach ($routers as $routerName => $routerConfig){
                //先看是不是路由组，如果是路由组，进入路由组切割
                $groups     = $routerConfig['groups'] ?? [];
                if($groups) {
                    foreach ($groups as $name => $group){
                        $this->addRoute($name, $groups);
                    }
                }else {
                    $this->addRoute($routerName, $routerConfig);
                }
            }
        }
    }

    /**
     * 添加路由
     *
     * @param string $router
     * @param array  $config
     */
    public function addRoute(string $router, array $config = []){
        if(empty($config)){
            return;
        }
        $method = $config['method'] ?? RouterMethod::GET;
        // 如果有handle 就使用handle，如果
        $handle = "";
        if(isset($config['handle'])) {
            $handle     = $config['handle'];
        } else {
            $handle     = $config['class'];
        }
        $this->getRouteCollector()->addRoute($method, $router, $handle);
    }
}
