<?php
declare(strict_types=1);

namespace Karthus\Spl;

class StrictArray implements \ArrayAccess ,\Countable ,\Iterator {
    private $class;
    private $data = [];
    private $currentKey;
    private $keys = [];

    /**
     * StrictArray constructor.
     *
     * @param string $itemClass
     */
    public function __construct(string $itemClass) {
        $this->class = $itemClass;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->data[ $offset ]);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset) {
        if (isset($this->data[ $offset ])) {
            return $this->data[ $offset ];
        } else {
            return null;
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return bool|void
     * @throws \Exception
     */
    public function offsetSet($offset, $value) {
        if (is_a($value, $this->class)) {
            $this->data[ $offset ] = $value;
            return true;
        }
        throw new \Exception("StrictArray can only set {$this->class} object");
    }

    /**
     * @param mixed $offset
     * @return bool|void
     */
    public function offsetUnset($offset) {
        if (isset($this->data[ $offset ])) {
            unset($this->data[ $offset ]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return int
     */
    public function count() {
        return count($this->data);
    }

    /**
     * @return mixed
     */
    public function current() {
        return $this->data[ $this->currentKey ];
    }


    public function next() {
        $this->currentKey = array_shift($this->keys);
    }

    /**
     * @return bool|float|int|string|null
     */
    public function key() {
        if ($this->currentKey === null) {
            $this->rewind();
        }
        return $this->currentKey;
    }

    /**
     * @return bool
     */
    public function valid() {
        return isset($this->data[ $this->currentKey ]);
    }

    /**
     *
     */
    public function rewind() {
        $this->currentKey = null;
        $this->keys = [];
        $this->keys = array_keys($this->data);
        $this->currentKey = array_shift($this->keys);
    }
}
