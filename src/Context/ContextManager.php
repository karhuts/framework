<?php
declare(strict_types=1);
namespace Karthus\Context;

use Karthus\Component\Singleton;
use Karthus\Exception\Exception;
use Swoole\Coroutine;

/**
 * 上下文管理
 *
 * Class ContextManager
 *
 * @package Karthus\Context
 */
class ContextManager {
    use Singleton;

    /**
     * @var array
     */
    private $contextHandler = [];

    /**
     * @var array
     */
    private $context = [];

    /**
     * @var array
     */
    private $deferList = [];

    /**
     * 注册
     *
     * @param                             $key
     * @param ContextItemHandlerInterface $handler
     * @return ContextManager
     */
    public function registerItemHandler($key, ContextItemHandlerInterface $handler):ContextManager {
        $this->contextHandler[$key] = $handler;
        return $this;
    }

    /**
     * 设置
     *
     * @param      $key
     * @param      $value
     * @param null $cid
     * @return ContextManager
     */
    public function set($key, $value, $cid = null) : ContextManager {
        if(isset($this->contextHandler[$key])){
            throw new Exception('key is already been register for context item handler');
        }
        $cid    = $this->getCid($cid);
        $this->context[$cid][$key] = $value;
        return $this;
    }

    /**
     * @param      $key
     * @param null $cid
     * @return mixed|null
     */
    public function get($key, $cid = null) {
        $cid = $this->getCid($cid);
        if(isset($this->context[$cid][$key])){
            return $this->context[$cid][$key];
        }
        if(isset($this->contextHandler[$key])){
            /** @var ContextItemHandlerInterface $handler */
            $handler = $this->contextHandler[$key];
            $this->context[$cid][$key] = $handler->onContextCreate();
            return $this->context[$cid][$key];
        }
        return null;
    }

    /**
     * @param      $key
     * @param null $cid
     * @return bool
     */
    public function unset($key, $cid = null): bool {
        $cid = $this->getCid($cid);
        if(isset($this->context[$cid][$key])){
            if(isset($this->contextHandler[$key])){
                /** @var ContextItemHandlerInterface $handler */
                $handler = $this->contextHandler[$key];
                $item = $this->context[$cid][$key];
                unset($this->context[$cid][$key]);
                return $handler->onDestroy($item);
            }
            unset($this->context[$cid][$key]);
            return true;
        }

        return false;
    }

    /**
     * @param null $cid
     */
    public function destroy($cid = null): void {
        $cid = $this->getCid($cid);
        if(isset($this->context[$cid])){
            $data = $this->context[$cid];
            foreach ($data as $key => $val){
                $this->unset($key,$cid);
            }
        }
        unset($this->context[$cid]);
    }

    /**
     * @param null $cid
     * @return int
     */
    public function getCid($cid = null):?int {
        if($cid === null){
            $cid = Coroutine::getUid();
            if(!isset($this->deferList[$cid]) && $cid > 0){
                $this->deferList[$cid] = true;
                Coroutine::defer(function ()use($cid){
                    unset($this->deferList[$cid]);
                    $this->destroy($cid);
                });
            }
            return $cid;
        }
        return $cid;
    }

    /**
     * @param bool $force
     */
    public function destroyAll($force = false) {
        if($force){
            $this->context = [];
        }else{
            foreach ($this->context as $cid => $data){
                $this->destroy($cid);
            }
        }
    }

    /**
     * @param null $cid
     * @return array|null
     */
    public function getContextArray($cid = null):?array {
        $cid = $this->getCid($cid);
        return $this->context[$cid] ?? null;
    }
}
