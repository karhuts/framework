<?php
declare(strict_types=1);
namespace Karthus\Config;

/**
 * Class AbstractConfig
 *
 * @package Karthus\Config
 */
abstract class AbstractConfig {
    /**
     * 获取配置文件
     * @param null $key
     * @return mixed
     */
    abstract public function getConf($key = null);

    /**
     * 设置配置
     * @param $key
     * @param $val
     * @return bool
     */
    abstract public function setConf($key,$val):bool ;

    /**
     * 加载配置
     *
     * @param array $array
     * @return bool
     */
    abstract public function load(array $array):bool ;

    /**
     * 合并配置
     * @param array $array
     * @return bool
     */
    abstract public function merge(array $array):bool ;

    /**
     * 清除
     * @return bool
     */
    abstract public function clear():bool ;
}
