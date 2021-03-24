<?php
declare(strict_types=1);
namespace Karthus\Component;

class Container {
    private $container = [];
    private $allowKeys;

    /**
     * Container constructor.
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
    public function set($key, $item) {
        if (is_array($this->allowKeys) && !in_array($key, $this->allowKeys)) {
            return false;
        }
        $this->container[ $key ] = $item;
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function delete($key): Container {
        if (isset($this->container[ $key ])) {
            unset($this->container[ $key ]);
        }
        return $this;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function get($key) {
        return $this->container[$key] ?? null;
    }

    public function clear(): void{
        $this->container = [];
    }

    public function all(): array {
        return $this->container;
    }
}
