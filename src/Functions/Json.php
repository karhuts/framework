<?php
declare(strict_types=1);

namespace Karthus\Functions;

use InvalidArgumentException;
use Karthus\Contract\Able\Arrayable;
use Karthus\Contract\Able\Jsonable;

class Json {

    /***
     * @param     $data
     * @param int $options
     * @return string
     */
    public static function encode($data, $options = JSON_UNESCAPED_UNICODE): string {
        if ($data instanceof Jsonable) {
            return (string) $data;
        }
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }
        $json = json_encode($data, $options);
        static::handleJsonError(json_last_error(), json_last_error_msg());
        return $json;
    }

    /**
     * @param string $json
     * @param bool   $assoc
     * @return mixed
     */
    public static function decode(string $json, $assoc = true) {
        $decode = json_decode($json, $assoc);
        static::handleJsonError(json_last_error(), json_last_error_msg());
        return $decode;
    }

    /**
     * @param $lastError
     * @param $message
     */
    protected static function handleJsonError($lastError, $message) {
        if ($lastError === JSON_ERROR_NONE) {
            return;
        }
        throw new InvalidArgumentException($message, $lastError);
    }

}
