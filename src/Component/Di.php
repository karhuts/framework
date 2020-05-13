<?php
declare(strict_types=1);
namespace Karthus\Component;

/**
 * 依赖注入
 *
 * Class Di
 *
 * @package Karthus\Component
 */
class Di {
    use Singleton;
    private $container = array();

    /**
     * @param       $key
     * @param       $obj
     * @param mixed ...$arg
     */
    public function set($key, $obj,...$arg):void {
        $this->container[$key] = array(
            "obj"       => $obj,
            "params"    => $arg,
        );
    }

    /**
     *
     * 删除
     *
     * @param $key
     */
    public function delete($key):void {
        unset($this->container[$key]);
    }

    /**
     * 清除
     */
    public function clear():void {
        $this->container = array();
    }

    /**
     * @param $key
     * @return null
     * @throws \Throwable
     */
    public function get($key) {
        if(isset($this->container[$key])){
            $obj    = $this->container[$key]['obj'];
            $params = $this->container[$key]['params'];
            if(is_object($obj) || is_callable($obj)){
                return $obj;
            }elseif(is_string($obj) && class_exists($obj)){
                try{
                    $this->container[$key]['obj'] = new $obj(...$params);
                    return $this->container[$key]['obj'];
                }catch (\Throwable $throwable){
                    throw $throwable;
                }
            }else{
                return $obj;
            }
        }else{
            return null;
        }
    }
}
