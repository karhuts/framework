<?php
use \Karthus\Service\ApplicationContext;

/**
 * Call a callback with the arguments.
 *
 * @param mixed $callback
 * @return null|mixed
 */
function call($callback, array $args = []) {
    $result = null;
    if ($callback instanceof \Closure) {
        $result = $callback(...$args);
    } elseif (is_object($callback) || (is_string($callback) && function_exists($callback))) {
        $result = $callback(...$args);
    } elseif (is_array($callback)) {
        [$object, $method] = $callback;
        $result = is_object($object) ? $object->{$method}(...$args) : $object::$method(...$args);
    } else {
        $result = call_user_func_array($callback, $args);
    }
    return $result;
}

/**
 * Create a object instance, if the DI container exist in ApplicationContext,
 * then the object will be create by DI container via `make()` method, if not,
 * the object will create by `new` keyword.
 */
function make(string $name, array $parameters = []) {
    if (ApplicationContext::hasContainer()) {
        $container = ApplicationContext::getContainer();
        if (method_exists($container, 'make')) {
            return $container->make($name, $parameters);
        }
    }
    $parameters = array_values($parameters);
    return new $name(...$parameters);
}
