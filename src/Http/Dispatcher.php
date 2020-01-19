<?php
declare(strict_types=1);
namespace Karthus\Http;

use Karthus\Core;
use Karthus\Exception\ControllerPoolEmpty;
use Karthus\Exception\RouterError;
use Karthus\Http\AbstractInterface\AbstractRouter;
use Karthus\Http\AbstractInterface\Controller;
use Karthus\Http\Router\Router;
use Swoole\Coroutine as Co;
use FastRoute\Dispatcher\GroupCountBased;
use Swoole\Http\Status;


class Dispatcher {
    private $router = null;
    private $routerRegister = null;
    private $controllerNameSpacePrefix;
    private $maxDepth;
    private $maxPoolNum;
    private $controllerPoolCreateNum = [];
    private $httpExceptionHandler = null;
    private $controllerPoolWaitTime = 5.0;

    /**
     * Dispatcher constructor.
     *
     * @param string $controllerNameSpace
     * @param int    $maxDepth
     * @param int    $maxPoolNum
     */
    public function __construct(string $controllerNameSpace,
                                int $maxDepth = 5,
                                int $maxPoolNum = 200) {
        $this->controllerNameSpacePrefix = trim($controllerNameSpace,'\\');
        $this->maxPoolNum                = $maxPoolNum;
        $this->maxDepth                  = $maxDepth;
    }

    /**
     * @param float $controllerPoolWaitTime
     */
    public function setControllerPoolWaitTime(float $controllerPoolWaitTime): void {
        $this->controllerPoolWaitTime = $controllerPoolWaitTime;
    }

    /**
     * @param callable $handler
     */
    public function setHttpExceptionHandler(callable $handler):void {
        $this->httpExceptionHandler = $handler;
    }

    /**
     * 路由
     *
     * @param Request  $request
     * @param Response $response
     */
    public function dispatch(Request $request, Response $response):void {
        // 初始化？
        if($this->router === null){
            try{
                $ref = new \ReflectionClass(Router::class);
                if($ref->isSubclassOf(AbstractRouter::class)){
                    $this->routerRegister =  $ref->newInstance();
                    $this->router = new GroupCountBased($this->routerRegister->getRouteCollector()->getData());
                }else{
                    $this->router = false;
                    throw new RouterError("class : Router not AbstractRouter class");
                }
            }catch (\Throwable $throwable){
                $this->router = false;
                throw new RouterError($throwable->getMessage());
            }
        }
        $path = UriPathInfo($request->getUri()->getPath());
        if($this->router instanceof GroupCountBased){
            $handler = null;
            $routeInfo = $this->router->dispatch($request->getMethod(), $request->getUri()->getPath());
            if($routeInfo !== false){
                switch ($routeInfo[0]) {
                    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                        $handler = $this->routerRegister->getMethodNotAllowCallBack();
                        break;
                    case \FastRoute\Dispatcher::FOUND:
                        $handler    = $routeInfo[1];
                        //合并解析出来的数据
                        $vars       = $routeInfo[2];
                        $data       = $request->getQueryParams();
                        $request->withQueryParams($vars + $data);
                        break;
                    case \FastRoute\Dispatcher::NOT_FOUND:
                    default:
                        $handler = $this->routerRegister->getRouterNotFoundCallBack();
                        break;
                }
            }
            //如果handler不为null，那么说明，非为 \FastRoute\Dispatcher::FOUND ，因此执行
            if(is_callable($handler)){
                try{
                    //若直接返回一个url path
                    $ret = call_user_func($handler,$request,$response);
                    if(is_string($ret)){
                        $path = UriPathInfo($ret);
                    }else if($ret == false){
                        return;
                    }else{
                        //可能在回调中重写了URL PATH
                        $path = UriPathInfo($request->getUri()->getPath());
                    }
                    $request->getUri()->withPath($path);
                }catch (\Throwable $throwable){
                    $this->hookThrowable($throwable,$request,$response);
                    //出现异常的时候，不在往下dispatch
                    return;
                }
            }elseif(is_string($handler)){
                $path = UriPathInfo($handler);
                $request->getUri()->withPath($path);
                goto response;
            }
            /*
                * 全局模式的时候，都拦截。非全局模式，否则继续往下
            */
            if($this->routerRegister->isGlobalMode()){
                return;
            }
        }
        response:{
            $this->controllerHandler($request,$response,$path);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $path
     */
    private function controllerHandler(Request $request,Response $response,string $path) {
        $pathInfo   = ltrim($path,"/");
        $list       = explode("/", $pathInfo);
        $actionName = null;
        $finalClass = null;
        $controlMaxDepth    = $this->maxDepth;
        $currentDepth       = count($list);
        $maxDepth           = $currentDepth < $controlMaxDepth ? $currentDepth : $controlMaxDepth;
        while ($maxDepth >= 0){
            $className      = '';
            for ($i=0 ;$i<$maxDepth; $i++){
                $className = $className."\\".ucfirst($list[$i] ?: 'Index');//为一级控制器Index服务
            }
            if(class_exists($this->controllerNameSpacePrefix.$className)){
                //尝试获取该class后的actionName
                $actionName = empty($list[$i]) ? 'execute' : $list[$i];
                $finalClass = $this->controllerNameSpacePrefix.$className;
                break;
            }else{
                //尝试搜搜index控制器
                $temp = $className."\\Index";
                if(class_exists($this->controllerNameSpacePrefix.$temp)){
                    $finalClass = $this->controllerNameSpacePrefix.$temp;
                    //尝试获取该class后的actionName
                    $actionName = empty($list[$i]) ? 'execute' : $list[$i];
                    break;
                }
            }
            $maxDepth--;
        }

        if(!empty($finalClass)){
            try{
                $controllerObject = $this->getController($finalClass);
            }catch (\Throwable $throwable){
                $this->hookThrowable($throwable,$request,$response);
                return;
            }
            if($controllerObject instanceof Controller){
                try{
                    $forward = $controllerObject->__hook($actionName,$request,$response);
                    if(is_string($forward) && (strlen($forward) > 0) && ($forward != $path)){
                        $forward = UriPathInfo($forward);
                        $request->getUri()->withPath($forward);
                        $this->dispatch($request,$response);
                    }
                }catch (\Throwable $throwable){
                    $this->hookThrowable($throwable,$request,$response);
                }finally {
                    $this->recycleController($finalClass,$controllerObject);
                }
            }else{
                $throwable = new ControllerPoolEmpty("controller pool empty for $finalClass");
                $this->hookThrowable($throwable,$request,$response);
            }
        }else{
            $response->withStatus(Status::NOT_FOUND);
            $response->write("not controller class match");

        }
    }

    /**
     * @param string $class
     * @return mixed
     * @throws \Throwable
     */
    protected function getController(string $class) {
        $classKey = $this->generateClassKey($class);
        if(!isset($this->$classKey)){
            $this->$classKey = new Co\Channel($this->maxPoolNum + 1);
            $this->controllerPoolCreateNum[$classKey] = 0;
        }
        $channel = $this->$classKey;
        //懒惰创建模式
        /** @var Co\Channel $channel */
        if($channel->isEmpty()){
            $createNum = $this->controllerPoolCreateNum[$classKey];
            if($createNum < $this->maxPoolNum){
                $this->controllerPoolCreateNum[$classKey] = $createNum+1;
                try{
                    //防止用户在控制器结构函数做了什么东西导致异常
                    return new $class();
                }catch (\Throwable $exception){
                    $this->controllerPoolCreateNum[$classKey] = $createNum;
                    //直接抛给上层
                    throw $exception;
                }
            }
            return $channel->pop($this->controllerPoolWaitTime);
        }
        return $channel->pop($this->controllerPoolWaitTime);
    }

    /**
     * @param string     $class
     * @param Controller $obj
     */
    protected function recycleController(string $class,Controller $obj) {
        $classKey = $this->generateClassKey($class);
        /** @var Co\Channel $channel */
        $channel = $this->$classKey;
        $channel->push($obj);
    }

    /**
     * @param \Throwable $throwable
     * @param Request    $request
     * @param Response   $response\
     */
    protected function hookThrowable(\Throwable $throwable,Request $request,Response $response) {
        if(is_callable($this->httpExceptionHandler)){
            call_user_func($this->httpExceptionHandler,$throwable,$request,$response);
        }else{
            $response->withStatus(Status::INTERNAL_SERVER_ERROR);
            $response->write(nl2br($throwable->getMessage()."\n".$throwable->getTraceAsString()));
        }
    }

    /**
     * @param string $class
     * @return string
     */
    protected function generateClassKey(string $class):string {
        return substr(md5($class), 8, 16);
    }
}
