<?php
declare(strict_types=1);

namespace Karthus\Spl;

use ArrayObject;

class SplArray extends ArrayObject {

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        return $this[$name] ?? null;
    }

    public function __isset($name){}

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value): void {
        $this[$name] = $value;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array
     */
    public function getArrayCopy(): array {
        return (array)$this;
    }

    /**
     * @param $path
     * @param $value
     */
    public function set($path, $value): void {
        $path = explode(".", $path);
        $temp = $this;
        while ($key = array_shift($path)) {
            $temp = &$temp[ $key ];
        }
        $temp = $value;
    }

    /**
     * @param $path
     */
    public function unset($path): void {
        $finalKey = null;
        $path = explode(".", $path);
        $temp = $this;
        while (count($path) > 1 && $key = array_shift($path)) {
            $temp = &$temp[ $key ];
        }
        $finalKey = array_shift($path);
        if (isset($temp[ $finalKey ])) {
            unset($temp[ $finalKey ]);
        }
    }

    /**
     * @param $path
     * @return array|mixed|null
     */
    public function get($path): ?array{
        $paths = explode(".", $path);
        $data = $this->getArrayCopy();
        while ($key = array_shift($paths)) {
            if (isset($data[ $key ])) {
                $data = $data[ $key ];
            } else {
                if ($key === '*') {
                    $temp = [];
                    if (is_array($data)) {
                        if (!empty($paths)) {
                            $path = implode("/", $paths);
                        } else {
                            $path = null;
                        }
                        foreach ($data as $key => $datum) {
                            if (is_array($datum)) {
                                $ctemp = (new self($datum))->get($path);
                                if ($ctemp !== null) {
                                    $temp[][ $path ] = $ctemp;
                                }
                            } else if ($datum !== null) {
                                $temp[ $key ] = $datum;
                            }

                        }
                    }
                    return $temp;
                }

                return null;
            }
        }
        return $data;
    }

    /**
     * @param $key
     */
    public function delete($key): void {
        $this->unset($key);
    }

    /**
     * 数组去重取唯一的值
     *
     * @return SplArray
     */
    public function unique(): SplArray {
        return new self(array_unique($this->getArrayCopy(), SORT_REGULAR));
    }

    /**
     * 获取数组中重复的值
     *
     * @return SplArray
     */
    public function multiple(): SplArray {
        $unique_arr = array_unique($this->getArrayCopy(), SORT_REGULAR);
        return new self(array_udiff_uassoc($this->getArrayCopy(), $unique_arr, static function ($key1, $key2) {
            if ($key1 === $key2) {
                return 0;
            }
            return 1;
        }, static function ($value1, $value2) {
            if ($value1 === $value2) {
                return 0;
            }
            return 1;
        }));
    }

    /**
     * 按照键值升序
     *
     * @param int $flags
     * @return SplArray
     */
    public function asort($flags = SORT_REGULAR): SplArray {
        parent::asort($flags);
        return $this;
    }

    /**
     * 按照键升序
     *
     * @param int $flags
     * @return SplArray
     */
    public function ksort($flags = SORT_REGULAR): SplArray {
        parent::ksort($flags);
        return $this;
    }

    /**
     * 自定义排序
     *
     * @param int $sort_flags
     * @return SplArray
     */
    public function sort($sort_flags = SORT_REGULAR): SplArray {
        $temp = $this->getArrayCopy();
        sort($temp, $sort_flags);
        return new self($temp);
    }

    /**
     * 取得某一列
     *
     * @param string $column
     * @param null|string $index_key
     * @return SplArray
     */
    public function column(string $column, $index_key = null): SplArray {
        return new self(array_column($this->getArrayCopy(), $column, $index_key));
    }

    /**
     * 交换数组中的键和值
     *
     * @return SplArray
     */
    public function flip(): SplArray {
        return new self(array_flip($this->getArrayCopy()));
    }

    /**
     * 过滤本数组
     *
     * @param string|array $keys    需要取得/排除的键
     * @param bool         $exclude true则排除设置的键名 false则仅获取设置的键名
     * @return SplArray
     */
    public function filter($keys, $exclude = false): SplArray {
        if (is_string($keys)) {
            $keys = explode(',', $keys);
        }
        $new = array();
        foreach ($this->getArrayCopy() as $name => $value) {
            if (!$exclude) {
                in_array($name, $keys) ? $new[ $name ] = $value : null;
            } else {
                in_array($name, $keys) ? null : $new[ $name ] = $value;
            }
        }
        return new self($new);
    }


    /**
     * @param null $path
     * @return array
     */
    public function keys($path = null): array {
        if (!empty($path)) {
            $temp = $this->get($path);
            if (is_array($temp)) {
                return array_keys($temp);
            }

            return [];
        }
        return array_keys((array) $this);
    }

    /**
     * 提取数组中的值
     *
     * @return SplArray
     */
    public function values(): SplArray {
        return new self(array_values($this->getArrayCopy()));
    }

    /**
     * @return SplArray
     */
    public function flush(): SplArray {
        foreach ($this->getArrayCopy() as $key => $item) {
            unset($this[ $key ]);
        }
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function loadArray(array $data): SplArray {
        $this->__construct($data);
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function merge(array $data): SplArray {
        return $this->loadArray($data + $this->getArrayCopy());
    }
}
