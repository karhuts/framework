<?php
declare(strict_types=1);

namespace Karthus\Spl;

/**
 * Class SplString
 *
 * @package Karthus\Spl
 */
class SplString extends SplStream {

    /**
     * SplString constructor.
     *
     * @param string|null $str
     */
    public function __construct(string $str = null) {
        parent::__construct($str);
    }

    /**
     * @param string $string
     * @return SplString
     */
    public function setString(string $string): SplString {
        parent::truncate();
        parent::rewind();
        parent::write($string);
        return $this;
    }

    /**
     * @param int $length
     * @return SplArray
     */
    public function split(int $length = 1): SplArray {
        return new SplArray(str_split($this->__toString(), $length));
    }

    /**
     * @param string $delimiter
     * @return SplArray
     */
    public function explode(string $delimiter): SplArray {
        return new SplArray(explode($delimiter, $this->__toString()));
    }

    /**
     * @param int $start
     * @param int $length
     * @return SplString
     */
    public function subString(int $start, int $length): SplString {
        return $this->setString(substr($this->__toString(), $start, $length));
    }

    /**
     * @param string $desEncoding
     * @param array  $detectList
     * @return SplString
     */
    public function encodingConvert(
        string $desEncoding,
        array $detectList = [
        'UTF-8',
        'ASCII',
        'GBK',
        'GB2312',
        'LATIN1',
        'BIG5',
        "UCS-2",
    ]): SplString {
        $fileType = mb_detect_encoding($this->__toString(), $detectList);
        if ($fileType != $desEncoding) {
            $this->setString(mb_convert_encoding($this->__toString(), $desEncoding, $fileType));
        }
        return $this;
    }

    /**
     * @return SplString
     */
    public function utf8(): SplString {
        return $this->encodingConvert("UTF-8");
    }

    /**
     * @return SplString
     */
    public function unicodeToUtf8(): SplString {
        $string = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', function ($matches) {
            return mb_convert_encoding(pack("H*", $matches[ 1 ]), "UTF-8", "UCS-2BE");
        }, $this->__toString());
        return $this->setString($string);
    }

    /**
     * @return SplString
     */
    public function toUnicode(): SplString {
        $raw = (string) $this->encodingConvert("UCS-2");
        $len = strlen($raw);
        $str = '';
        for ($i = 0; $i < $len - 1; $i = $i + 2) {
            $c = $raw[ $i ];
            $c2 = $raw[ $i + 1 ];
            if (ord($c) > 0) {   //两个字节的文字
                $str .= '\u' . base_convert(ord($c), 10, 16) . str_pad(base_convert(ord($c2), 10, 16), 2, 0, STR_PAD_LEFT);
            } else {
                $str .= '\u' . str_pad(base_convert(ord($c2), 10, 16), 4, 0, STR_PAD_LEFT);
            }
        }
        $string = strtoupper($str);//转换为大写
        return $this->setString($string);
    }

    /**
     * @param string $str
     * @param int    $ignoreCase
     * @return int
     */
    public function compare(string $str, int $ignoreCase = 0): int {
        if ($ignoreCase) {
            return strcasecmp($this->__toString(), $str);
        } else {
            return strcmp($this->__toString(), $str);
        }
    }

    /**
     * @param string $charList
     * @return SplString
     */
    public function lTrim(string $charList = " \t\n\r\0\x0B"): SplString {
        return $this->setString(ltrim($this->__toString(), $charList));
    }

    /**
     * @param string $charList
     * @return SplString
     */
    public function rTrim(string $charList = " \t\n\r\0\x0B"): SplString {
        return $this->setString(rtrim($this->__toString(), $charList));
    }

    /**
     * @param string $charList
     * @return SplString
     */
    public function trim(string $charList = " \t\n\r\0\x0B"): SplString {
        return $this->setString(trim($this->__toString(), $charList));
    }

    /**
     * @param int         $length
     * @param string|null $padString
     * @param int         $pad_type
     * @return SplString
     */
    public function pad(int $length, string $padString = null, int $pad_type = STR_PAD_RIGHT): SplString {
        return $this->setString(str_pad($this->__toString(), $length, $padString, $pad_type));
    }

    /**
     * @param int $times
     * @return SplString
     */
    public function repeat(int $times): SplString {
        return $this->setString(str_repeat($this->__toString(), $times));
    }

    /**
     * @return int
     */
    public function length(): int {
        return strlen($this->__toString());
    }

    /**
     * @return SplString
     */
    public function upper(): SplString {
        return $this->setString(strtoupper($this->__toString()));
    }

    /**
     * @return SplString
     */
    public function lower(): SplString {
        return $this->setString(strtolower($this->__toString()));
    }

    /**
     * @param string|null $allowable_tags
     * @return SplString
     */
    public function stripTags(string $allowable_tags = null): SplString {
        return $this->setString(strip_tags($this->__toString(), $allowable_tags));
    }

    /**
     * @param string $find
     * @param string $replaceTo
     * @return SplString
     */
    public function replace(string $find, string $replaceTo): SplString {
        return $this->setString(str_replace($find, $replaceTo, $this->__toString()));
    }

    /**
     * @param string $startStr
     * @param string $endStr
     * @return SplString
     */
    public function between(string $startStr, string $endStr): SplString {
        $explode_arr = explode($startStr, $this->__toString());
        if (isset($explode_arr[ 1 ])) {
            $explode_arr = explode($endStr, $explode_arr[ 1 ]);
            return $this->setString($explode_arr[ 0 ]);
        } else {
            return $this->setString('');
        }
    }

    /**
     * @param      $regex
     * @param bool $rawReturn
     * @return mixed|null
     */
    public function regex($regex, bool $rawReturn = false) {
        preg_match($regex, $this->__toString(), $result);
        if (!empty($result)) {
            if ($rawReturn) {
                return $result;
            } else {
                return $result[ 0 ];
            }
        } else {
            return null;
        }
    }

    /**
     * @param string $find
     * @param bool   $ignoreCase
     * @return bool
     */
    public function exist(string $find, bool $ignoreCase = true): bool {
        if ($ignoreCase) {
            $label = stripos($this->__toString(), $find);
        } else {
            $label = strpos($this->__toString(), $find);
        }
        return $label === false ? false : true;
    }

    /**
     * @return SplString
     */
    public function kebab(): SplString {
        return $this->snake('-');
    }

    /**
     * @param string $delimiter
     * @return SplString
     */
    public function snake(string $delimiter = '_'): SplString {
        $string = $this->__toString();
        if (!ctype_lower($string)) {
            $string = preg_replace('/\s+/u', '', ucwords($this->__toString()));
            $string = $this->setString(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $string));
            $this->setString((string) $string);
            $this->lower();
        }
        return $this;
    }


    /**
     * @return SplString
     */
    public function studly(): SplString {
        $value = ucwords(str_replace(['-', '_'], ' ', $this->__toString()));
        return $this->setString(str_replace(' ', '', $value));
    }

    /**
     * 驼峰
     *
     * @return SplString
     */
    public function camel(): SplString {
        $this->studly();
        return $this->setString(lcfirst($this->__toString()));
    }


    /**
     * 用数组逐个字符
     *
     * @param string $search
     * @param array  $replace
     * @return SplString
     */
    public function replaceArray(string $search, array $replace): SplString {
        foreach ($replace as $value) {
            $this->setString((string) $this->replaceFirst($search, $value));
        }
        return $this;
    }

    /**
     * 替换字符串中给定值的第一次出现。
     *
     * @param string $search
     * @param string $replace
     * @return SplString
     */
    public function replaceFirst(string $search, string $replace): SplString {
        if ($search == '') {
            return $this;
        }

        $position = strpos($this->__toString(), $search);

        if ($position !== false) {
            return $this->setString(substr_replace($this->__toString(), $replace, $position, strlen($search)));
        }

        return $this;
    }

    /**
     * 替换字符串中给定值的最后一次出现。
     *
     * @param string $search
     * @param string $replace
     * @return SplString
     */
    public function replaceLast(string $search, string $replace): SplString {
        $position = strrpos($this->__toString(), $search);

        if ($position !== false) {
            return $this->setString(substr_replace($this->__toString(), $replace, $position, strlen($search)));
        }

        return $this;
    }

    /**
     * 以一个给定值的单一实例开始一个字符串
     *
     * @param string $prefix
     * @return SplString
     */
    public function start(string $prefix): SplString {
        $quoted = preg_quote($prefix, '/');
        return $this->setString($prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $this->__toString()));
    }

    /**
     * 在给定的值之后返回字符串的其余部分。
     *
     * @param string $search
     * @return SplString
     */
    function after(string $search): SplString {
        if ($search === '') {
            return $this;
        } else {
            return $this->setString(array_reverse(explode($search, $this->__toString(), 2))[ 0 ]);
        }
    }

    /**
     * 在给定的值之前获取字符串的一部分
     *
     * @param string $search
     * @return SplString
     */
    function before(string $search): SplString {
        if ($search === '') {
            return $this;
        } else {
            return $this->setString(explode($search, $this->__toString())[ 0 ]);
        }
    }

    /**
     * 确定给定的字符串是否以给定的子字符串结束
     *
     * @param string|array $needles
     * @return bool
     */
    public function endsWith($needles): bool {
        foreach ((array) $needles as $needle) {
            if (substr($this->__toString(), -strlen($needle)) === (string) $needle) {
                return true;
            }
        }
        return false;
    }

    /**
     * 确定给定的字符串是否从给定的子字符串开始
     *
     * @param string|array $needles
     * @return bool
     */
    public function startsWith($needles): bool {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && substr($this->__toString(), 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }
        return false;
    }
}
