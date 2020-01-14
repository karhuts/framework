<?php
declare(strict_types=1);
namespace Karthus\Core\Help;

class Json {
    /**
     * 编码
     * @param $value
     * @param int $options
     * @param int $depth
     * @return string
     */
    public static function encode($value, $options = 0, $depth = 512) {
        return json_encode($value, $options, $depth);
    }
    /**
     * 解码
     * @param $json
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return mixed
     */
    public static function decode($json, $assoc = false, $depth = 512, $options = 0) {
        return json_decode($json, $assoc, $depth, $options);
    }
}
