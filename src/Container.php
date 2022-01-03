<?php
declare(strict_types=1);

namespace Karthus;

use Swoole\Coroutine;

class Container {
    use Singleton;

    /** @var array */
    protected $singletons;

    /** @var array */
    protected $scope;

    /**
     * 获取协程上下文.
     * @return null|Container
     */
    public function scope(): ?Container {
        $uid = Coroutine::getuid();
        if ($uid < 0) {
            return null;
        }
        if (! isset($this->scope[$uid])) {
            $this->scope[$uid] = new self();
        }
        return $this->scope[$uid];
    }

    /**
     * 回收协程上下文.
     */
    public function deleteScope(): void {
        $uid = Coroutine::getuid();
        unset($this->scope[$uid]);
    }

    /*
     * @method singleton("key", new App\Controller)
     * @method singleton("key", App\Controller::class)
     * @method singleton("key", function() { return new Object; })
     */
    public function set($key, $object) {
        if (is_callable($object)) {
            return $this->singletons[$key] = $object();
        }
        if (is_object($object)) {
            return $this->singletons[$key] = $object;
        }
        return $this->singletons[$key] = $this->singleton($object);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool {
        return isset($this->singletons[$id]);
    }

    /**
     * @param string $id
     * @return mixed|null
     */
    public function get(string $id) {
        return $this->singletons[$id] ?? null;
    }

    /*
     * @method singleton(App\Controller::class, ...$args)
     * @method singleton(App\Controller::class, function() { return new Object; })
     * @method singleton(new App\Controller)
     */
    public function singleton($id, ...$args) {
        if (is_object($id)) {
            $this->singletons[get_class($id)] = $id;
            return $id;
        }
        if (! isset($this->singletons[$id])) {
            if (is_callable($args[0] ?? null)) {
                $this->singletons[$id] = $args[0]();
            } else {
                $this->singletons[$id] = new $id(...$args);
            }
        }
        return $this->singletons[$id];
    }

    /*
     * @method call("App\Controller@index", ...$args)
     * @method call(App\Controller::class, ...$args)
     * @method call(new App\Controller, ...$args)
     */
    public function call($id, ...$args) {
        if (is_string($id)) {
            if (strpos($id, '@') !== false) {
                [$id, $action] = explode('@', $id);
            } else {
                $action = 'handle';
            }
            $instance = $this->singleton($id);
        } else {
            $instance = $id;
            $action = 'handle';
        }
        return $instance->{$action}(...$args);
    }

    /*
     * @method injectionProperty(new Object, [ key => val ])
     * @method injectionProperty(App\Controller::class, [ key => val ])
     */
    public function injectionProperty($id, $args): void{
        if (! is_object($id)) {
            $id = $this->get($id);
        }
        if (method_exists($id, '_property_injection_')) {
            $id->_property_injection_($args);
        }
    }
}
