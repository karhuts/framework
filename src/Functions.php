<?php
declare(strict_types=1);
use Karthus\Helper\FileHelper;
use Karthus\I18N\I18N;

if(!function_exists('welcome')){

    /**
     * 欢迎
     *
     * @return string
     */
    function welcome() : string {
        return <<<EOL
 _  __          _   _
 | |/ /__ _ _ __| |_| |__  _   _ ___
 | ' // _` | '__| __| '_ \| | | / __|
 | . \ (_| | |  | |_| | | | |_| \__ \
 |_|\_\__,_|_|   \__|_| |_|\__,_|___/\n\n
EOL;
    }
}

if(!function_exists("opCacheClear")){
    /**
     * 清理opcache
     */
    function opCacheClear() :void {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('apcu_clear_cache')){
            apcu_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}

if(!function_exists('displayItem')){
    /***
     * 显示item信息
     *
     * @param $name
     * @param $value
     * @return string
     */
    function displayItem($name, $value): string {
        if($value === true){
            $value = 'true';
        }else if($value === false){
            $value = 'false';
        }else if($value === null){
            $value = 'null';
        }

        return "\e[32m" . str_pad($name, 30, ' ', STR_PAD_RIGHT) . "\e[34m" . $value . "\e[0m";
    }
}

if(!function_exists('getLocalIP')){
    /**
     * @return array
     */
    function getLocalIP(): array{
        return swoole_get_local_ip();
    }
}

if(!function_exists('releaseResource')){
    /**
     * 释放资源，覆盖资源
     *
     * @param $source
     * @param $destination
     */
    function releaseResource($source, $destination){
        clearstatcache();
        $replace        = true;
        if (is_file($destination)) {
            $filename   = basename($destination);
            echo "{$filename} has already existed, do you want to replace it? [ Y / N (default) ] : ";
            $answer     = strtolower(strtoupper(trim(fgets(STDIN))));
            if (!in_array($answer, ['y', 'yes'])) {
                $replace = false;
            }
        }
        if ($replace) {
            FileHelper::copyFile($source, $destination);
        }
    }
}

if(!function_exists('UriPathInfo')){
    /**
     * @param string $path
     * @return string
     */
    function UriPathInfo(string $path = ''): string {
        $basePath   = dirname($path);
        $info       = pathInfo($path);
        if($info['filename'] !== 'index'){
            if($basePath === '/'){
                $basePath .= $info['filename'];
            }else{
                $basePath .= '/' . $info['filename'];
            }
        }
        return $basePath;
    }
}

if(!function_exists('isCli')){
    /**
     * 是否为 CLI 模式
     * @return bool
     */
    function isCli(): bool {
        return PHP_SAPI === 'cli';
    }
}

if(!function_exists('isWin')){
    /**
     * 是否为 Win 系统
     * @return bool
     */
    function isWin(): bool {
        if (isMac()) {
            return false;
        }
        return stripos(PHP_OS, 'WIN') !== false;
    }
}

if(!function_exists('isMac')){
    /**
     * 是否为 Mac 系统
     * @return bool
     */
    function isMac(): bool {
        return stripos(PHP_OS, 'Darwin') !== false;
    }
}

if(!function_exists('translate')) {

    /**
     * 翻译语言包
     *
     * @param string $key
     * @param array $replace
     * @param string $locale
     * @param bool $fallback
     * @return array|mixed|string
     */
    function translate(string $key, array $replace = [], string $locale = 'zh-CN', bool $fallback = false){
        return I18N::getInstance()->translate($key, $replace, $locale, $fallback);
    }
}


if(!function_exists('__')) {
    /**
     * 翻译语言包 translate 函数的别名
     *
     * @param string $key
     * @param array $replace
     * @param string $locale
     * @param bool $fallback
     * @return array|mixed|string
     */
    function __(string $key, array $replace = [], string $locale = 'zh-CN', bool $fallback = false){
        return translate($key, $replace, $locale, $fallback);
    }
}
