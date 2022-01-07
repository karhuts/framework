<?php
declare(strict_types=1);

namespace Karthus;

class Collection implements \ArrayAccess {
    protected $data;

    /**
     * @param $data
     */
    public function __construct($data) {
        if ($data instanceof self) {
            $this->data = $data->get();
        } else {
            $this->data = $data;
        }
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool {
        // 判断如果有 .
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $id = &$this->data;
            foreach ($keys as $key) {
                if (isset($id[$key])) {
                    $id = &$id[$key];
                } else {
                    return false;
                }
            }
            return true;
        }
        return isset($this->data[$key]);
    }

    /**
     * @param $key
     * @param $default
     * @return mixed|null
     */
    public function get($key = null, $default = null) {
        if ($key === null) {
            return $this->data;
        }
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $id = &$this->data;
            foreach ($keys as $key) {
                if (isset($id[$key])) {
                    $id = &$id[$key];
                } else {
                    return null;
                }
            }
            return $id;
        }
        return $this->data[$key] ?? $default;
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function set($key, $value): void {
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $id = &$this->data;
            foreach ($keys as $key) {
                if (isset($id[$key])) {
                    $id = &$id[$key];
                } else {
                    return;
                }
            }
            $id = $value;
        } else {
            $this->data[$key] = $value;
        }
    }

    /**
     * @return mixed|null
     */
    public function first() {
        return $this->data[0] ?? null;
    }

    /**
     * @param $call
     * @return array
     */
    public function filter($call): array {
        $result = [];
        foreach ($this->data as $k => $v) {
            if ($call($v, $k)) {
                $result[] = $v;
            }
        }
        return $result;
    }

    /**
     * @return mixed|null
     */
    public function toArray() {
        return $this->data;
    }

    /**
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset): bool {
        return isset($this->data[$offset]);
    }

    /**
     * @param $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->data[$offset];
    }

    /**
     * @param $offset
     * @param $value
     * @return void
     */
    public function offsetSet($offset, $value): void{
        $this->data[$offset] = $value;
    }

    /**
     * @param $offset
     * @return void
     */
    public function offsetUnset($offset): void{
        unset($this->data[$offset]);
    }
}