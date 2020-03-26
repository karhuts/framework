<?php
declare(strict_types=1);
namespace Karthus\Component;

class Event extends Container {
    /**
     * @param $key
     * @param $item
     * @return bool|Container
     */
    public function set($key, $item) {
        if (is_callable($item)) {
            return parent::set($key, $item);
        } else {
            return false;
        }
    }

    /**
     * @param       $event
     * @param mixed ...$args
     * @return mixed|null
     * @throws \Throwable
     */
    public function hook($event, ...$args) {
        $call = $this->get($event);
        if (is_callable($call)) {
            return call_user_func($call, ...$args);
        } else {
            return null;
        }
    }
}
