<?php
declare(strict_types=1);
namespace Karthus\Component;

/**
 * Class MultiContainer
 *
 * @package Karthus\Component
 */
class MultiContainer {
    private $container      = [];
    private $allowFunction  = null;

    /**
     * MultiContainer constructor.
     *
     * @param array|null $allowFunction
     */
    public function __construct(array $allowFunction = null) {
        $this->allowFunction = $allowFunction;
    }

    /**
     * 新增
     *
     * @param $key
     * @param $item
     * @return $this
     */
    public function add(string $key,$item) :MultiContainer{
        if(is_array($this->allowFunction) && !in_array($key,$this->allowFunction)){
            return $this;
        }
        $this->container[$key][] = $item;
        return $this;
    }

    /**
     * 设置
     *
     * @param $key
     * @param $item
     * @return $this|bool
     */
    public function set(string $key, $item):MultiContainer {
        if(is_array($this->allowFunction) && !in_array($key, $this->allowFunction)){
            return false;
        }
        $this->container[$key] = [$item];
        return $this;
    }

    /**
     * 删除单个
     *
     * @param $key
     * @return $this
     */
    public function delete(string $key): MultiContainer {
        if(isset($this->container[$key])){
            unset($this->container[$key]);
        }
        return $this;
    }

    /**
     * 获取单个
     *
     * @param $key
     * @return array|null
     */
    public function get(string $key):?array {
        if(isset($this->container[$key])){
            return $this->container[$key];
        }else{
            return null;
        }
    }

    /**
     * 获取所有
     *
     * @return array
     */
    public function all():array {
        return $this->container;
    }

    /**
     * 清空
     */
    public function clear() {
        $this->container = [];
    }
}
