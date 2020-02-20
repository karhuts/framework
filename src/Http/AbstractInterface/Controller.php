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
            'hook',
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
    abstract public function execute();

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
    public function hook(?string $actionName, Request $request, Response $response,callable $actionHook = null) {
        $forwardPath        = null;
        $this->request      = $request;
        $this->response     = $response;
        $this->actionName   = $actionName;
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
     * 输出JSON
     *
     * @param int   $status
     * @param array $data
     * @param bool  $isEnd
     * @return bool
     */
    protected function writeJson(int $status = 200, array $data = array(),bool $isEnd = true) {
        if (!$this->response()->isEndResponse()) {
            $requestParam   = $this->request()->getServerParams();
            $request_id     = $requestParam['request_id'] ?? '-';
            $request_time   = $requestParam['request_time_float'] ?? 0;
            $output         = array(
                'code'          => isset($data['code']) ? intval($data['code']) : $status,
                'message'       => isset($data['message']) ? strval($data['message']) : Status::getReasonPhrase($status),
                'data'          => isset($data['data']) ? $data['data'] : new \stdClass(),
                'request_id'    => $request_id,
                'request_time'  => floatval($request_time),
                'response_time' => microtime(true),
            );
            $this->response()
                ->withStatus($status)
                ->withHeader('Content-type', 'application/json; charset=utf-8')
                ->write(json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if($isEnd){
                $this->response()->end();
            }
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
