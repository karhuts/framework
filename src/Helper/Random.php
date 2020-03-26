<?php
declare(strict_types=1);
namespace Karthus\Helper;

class Random {
    private static $ALPHABET = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789';


    /**
     * 生成随机字符串 可用于生成随机密码等
     * @param int $length 生成长度
     * @param string $alphabet 自定义生成字符集
     * @return string
     */
    public static function character($length = 6): string {
        mt_srand();
        // 重复字母表以防止生成长度溢出字母表长度
        if ($length >= strlen(self::$ALPHABET)) {
            $rate = intval($length / strlen(self::$ALPHABET)) + 1;
            $alphabet = str_repeat(self::$ALPHABET, $rate);
        }

        // 打乱顺序返回
        return substr(str_shuffle($alphabet), 0, $length);
    }
}
