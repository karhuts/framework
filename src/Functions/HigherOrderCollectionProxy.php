<?php
declare(strict_types=1);

namespace Karthus\Functions;

/**
 * @mixin Collection
 * Most of the methods in this file come from illuminate/support,
 * thanks Laravel Team provide such a useful class.
 */
class HigherOrderCollectionProxy {
    /**
     * The collection being operated on.
     *
     * @var Collection
     */
    protected $collection;

    /**
     * The method being proxied.
     *
     * @var string
     */
    protected $method;

    /***
     * HigherOrderCollectionProxy constructor.
     *
     * @param Collection $collection
     * @param string     $method
     */
    public function __construct(Collection $collection, string $method) {
        $this->method = $method;
        $this->collection = $collection;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function __get(string $key) {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    /***
     * @param string $method
     * @param array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters) {
        return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
            return $value->{$method}(...$parameters);
        });
    }
}
