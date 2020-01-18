<?php
declare(strict_types=1);

namespace Karthus\Http\AbstractInterface;

use FastRoute\RouteParser\Std;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;

abstract class AbstractRouter {
    private $routeCollector;
    private $methodNotAllowCallBack = null;
    private $routerNotFoundCallBack = null;
    private $globalMode = false;

    /**
     * AbstractRouter constructor.
     */
    final function __construct() {
        $this->routeCollector = new RouteCollector(new Std(),new GroupCountBased());
        $this->initialize($this->routeCollector);
    }

    abstract function initialize(RouteCollector $routeCollector);

    /**
     * @return RouteCollector
     */
    public function getRouteCollector():RouteCollector {
        return $this->routeCollector;
    }


    /**
     * @param callable $call
     */
    public function setMethodNotAllowCallBack(callable $call) {
        $this->methodNotAllowCallBack = $call;
    }

    /**
     * @return null
     */
    public function getMethodNotAllowCallBack() {
        return $this->methodNotAllowCallBack;
    }

    /**
     * @return null
     */
    public function getRouterNotFoundCallBack() {
        return $this->routerNotFoundCallBack;
    }

    /**
     * @param null $routerNotFoundCallBack
     */
    public function setRouterNotFoundCallBack($routerNotFoundCallBack): void {
        $this->routerNotFoundCallBack = $routerNotFoundCallBack;
    }

    /**
     * @return bool
     */
    public function isGlobalMode(): bool {
        return $this->globalMode;
    }

    /**
     * @param bool $globalMode
     * @return void
     */
    public function setGlobalMode(bool $globalMode): void {
        $this->globalMode = $globalMode;
    }
}
