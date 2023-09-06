<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  294953530@qq.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\support;

use function base64_decode;
use function base64_encode;
use function md5;
use function microtime;
use function sprintf;
use function strlen;
use function substr;

class Encrypt
{
    /** @var string 加密密钥 Key */
    protected string $key = 'ff15XENqlxkqiF2F51cOiuIsIPcm1pQBhbZ5Po0';

    /** @var int 加密长度 */
    protected int $length = 4;

    protected int $expiry = 0;

    /**
     * @return $this
     */
    public function setKey(string $key): Encrypt
    {
        $this->key = $key === '' ? $this->key : $key;
        return $this;
    }

    /**
     * @return $this
     */
    public function setLength(int $length = 4): Encrypt
    {
        $this->length = $length;
        return $this;
    }

    /**
     * @return $this
     */
    public function setExpiry(int $expiry = 0): Encrypt
    {
        $this->expiry = $expiry;
        return $this;
    }

    public function encode(string $string): string
    {
        $expiry = $this->expiry;
        $ckey_length = $this->length;
        $key = md5($this->key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? substr(md5(microtime()), -$ckey_length) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = [];
        for ($i = 0; $i <= 255; ++$i) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; ++$i) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; ++$i) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ $box[($box[$a] + $box[$j]) % 256]);
        }

        return $keyc . str_replace('=', '', base64_encode($result));
    }

    /**
     * // 随机密钥长度 取值 0-32;
     * // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
     * // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
     * // 当此值为 0 时，则不产生随机密钥.
     */
    public function decode(string $string): string
    {
        $ckey_length = $this->length;
        $key = md5($this->key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? substr($string, 0, $ckey_length) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = base64_decode(substr($string, $ckey_length));
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = [];
        for ($i = 0; $i <= 255; ++$i) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; ++$i) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; ++$i) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ $box[($box[$a] + $box[$j]) % 256]);
        }

        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0)
            && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        }
        return '';
    }
}
