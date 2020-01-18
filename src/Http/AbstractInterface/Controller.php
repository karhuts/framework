<?php
declare(strict_types=1);

namespace Karthus\Http\AbstractInterface;

use Karthus\Http\Request;
use Karthus\Http\Response;
use Swoole\Http\Status;

abstract class Controller {
    private $request;
    private $response;
    private $actionName;
    private $defaultProperties = [];
    private $allowMethodReflections = [];
    private $propertyReflections = [];

    /**
     * Controller constructor.
     */
    public function __construct() {
        $forbidList = [
            '__hook',
            '__destruct',
            '__clone',
            '__construct',
            '__call',
            '__callStatic',
            '__get', '__set',
            '__isset',
            '__unset',
            '__sleep',
            '__wakeup',
            '__toString',
            '__invoke',
            '__set_state',
            '__clone',
            '__debugInfo',
            'onRequest'
        ];

        //支持在子类控制器中以private，protected来修饰某个方法不可见
        $ref = new \ReflectionClass(static::class);
        $public = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($public as $item) {
            if((!in_array($item->getName(), $forbidList)) && (!$item->isStatic())){
                $this->allowMethodReflections[$item->getName()] = $item;
            }
        }

        //获取，生成属性默认值
        $ref = new \ReflectionClass(static::class);
        $properties = $ref->getProperties();
        foreach ($properties as $property) {
            //不重置静态变量与保护私有变量
            if ($property->isPublic() && !$property->isStatic()) {
                $name = $property->getName();
                $this->defaultProperties[$name] = $this->{$name};
                $this->propertyReflections[$name] = $property;
            }
        }
    }

    /**
     * 可执行的
     *
     * @return mixed
     */
    abstract function execute();

    /**
     * @return array
     */
    protected function getAllowMethodReflections() {
        return $this->allowMethodReflections;
    }

    protected function getPropertyReflections():array {
        return $this->propertyReflections;
    }

    protected function gc() {
        //恢复默认值
        foreach ($this->defaultProperties as $property => $value) {
            $this->{$property} = $value;
        }
    }

    /**
     * @param string|null $action
     */
    protected function actionNotFound(?string $action) {
        $this->response()->withStatus(Status::NOT_FOUND);
    }

    protected function afterAction(?string $actionName): void {
    }

    /**
     * @param \Throwable $throwable
     * @throws \Throwable
     */
    protected function onException(\Throwable $throwable): void {
        throw $throwable;
    }

    /**
     * @param string|null $action
     * @return bool|null
     */
    protected function onRequest(?string $action): ?bool {
        return true;
    }

    /**
     * @return string|null
     */
    protected function getActionName(): ?string {
        return $this->actionName;
    }

    /**
     * @param string|null   $actionName
     * @param Request       $request
     * @param Response      $response
     * @param callable|null $actionHook
     * @return mixed|void|null
     * @throws \Throwable
     */
    public function __hook(?string $actionName, Request $request, Response $response,callable $actionHook = null) {
        $forwardPath = null;
        $this->request = $request;
        $this->response = $response;
        $this->actionName = $actionName;
        try {
            if ($this->onRequest($actionName) !== false) {
                if (isset($this->allowMethodReflections[$actionName])) {
                    if($actionHook){
                        $forwardPath = call_user_func($actionHook);
                    }else{
                        $forwardPath = $this->$actionName();
                    }
                } else {
                    $forwardPath = $this->actionNotFound($actionName);
                }
            }
        } catch (\Throwable $throwable) {
            //若没有重构onException，直接抛出给上层
            $this->onException($throwable);
        } finally {
            try {
                $this->afterAction($actionName);
            } catch (\Throwable $throwable) {
                $this->onException($throwable);
            } finally {
                try {
                    $this->gc();
                } catch (\Throwable $throwable) {
                    $this->onException($throwable);
                }
            }
        }
        return $forwardPath;
    }

    /**
     * @return Request
     */
    protected function request(): Request {
        return $this->request;
    }

    /**
     * @return Response
     */
    protected function response(): Response {
        return $this->response;
    }

    /**
     * @param int  $statusCode
     * @param null $result
     * @param null $msg
     * @return bool
     */
    protected function writeJson($statusCode = 200, $result = null, $msg = null) {
        if (!$this->response()->isEndResponse()) {
            $data = Array(
                "code"      => $statusCode,
                "result"    => $result,
                "msg"       => $msg
            );
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json; charset=utf-8');
            $this->response()->withStatus($statusCode);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array|null
     */
    protected function json(): ?array {
        return json_decode($this->request()->getBody()->__toString(), true);
    }

    /**
     * @param int    $options
     * @param string $className
     * @return \SimpleXMLElement
     */
    protected function xml($options = LIBXML_NOERROR | LIBXML_NOCDATA, string $className = 'SimpleXMLElement') {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return simplexml_load_string($this->request()->getBody()->__toString(), $className, $options);
    }
}
