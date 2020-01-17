<?php
declare(strict_types=1);
namespace Karthus\Component;

/**
 * Class MultiContainer
 *
 * @package Karthus\Component
 */
class MultiContainer {
    private $container = [];
    private $allowKeys = null;

    /**
     * MultiContainer constructor.
     *
     * @param array|null $allowKeys
     */
    public function __construct(array $allowKeys = null) {
        $this->allowKeys = $allowKeys;
    }

    /**
     * @param $key
     * @param $item
     * @return $this|bool
     */
    public function add($key,$item) {
        if(is_array($this->allowKeys) && !in_array($key,$this->allowKeys)){
            return false;
        }
        $this->container[$key][] = $item;
        return $this;
    }

    /**
     * @param $key
     * @param $item
     * @return $this|bool
     */
    public function set($key,$item) {
        if(is_array($this->allowKeys) && !in_array($key,$this->allowKeys)){
            return false;
        }
        $this->container[$key] = [$item];
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function delete($key) {
        if(isset($this->container[$key])){
            unset($this->container[$key]);
        }
        return $this;
    }

    /**
     * @param $key
     * @return array|null
     */
    public function get($key):?array {
        if(isset($this->container[$key])){
            return $this->container[$key];
        }else{
            return null;
        }
    }

    /**
     * @return array
     */
    public function all():array {
        return $this->container;
    }

    public function clear() {
        $this->container = [];
    }
}
