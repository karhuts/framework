<?php
declare(strict_types=1);

use Karthus\Collection;
use Karthus\Config;
use Karthus\Container;

if (!function_exists('getInstance')) {
    /**
     * @param $class
     * @return mixed
     */
    function getInstance($class) {
        return ($class)::getInstance();
    }
}


if (!function_exists('config')) {
    /**
     * @param $name
     * @param $default
     * @return mixed
     */
    function config($name, $default = null) {
        return getInstance(Config::class)->get($name, $default);
    }
}

if (!function_exists('container')) {
    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    function container($key = null, $value = null) {
        if ($key === null) {
            return Container::getInstance();
        }
        if ($value === null) {
            return Container::getInstance()->singleton($key);
        }
        return Container::getInstance()->set($key, $value);
    }
}

if (!function_exists('collection')) {
    /**
     * @param array $data
     * @return Collection
     */
    function collection(array $data = []): Collection {
        return new Collection($data);
    }
}