<?php
declare(strict_types=1);
use Karthus\Helper\FileHelper;

if(!function_exists('welcome')){

    /**
     * 欢迎
     *
     * @return string
     */
    function welcome() : string {
        $string =  <<<EOL
 _  __          _   _
 | |/ /__ _ _ __| |_| |__  _   _ ___
 | ' // _` | '__| __| '_ \| | | / __|
 | . \ (_| | |  | |_| | | | |_| \__ \
 |_|\_\__,_|_|   \__|_| |_|\__,_|___/\n
EOL;
        return $string;
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
    function displayItem($name, $value) {
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
     * 释放资源
     *
     * @param $source
     * @param $destination
     */
    function releaseResource($source, $destination){
        clearstatcache();
        $replace = true;
        if (is_file($destination)) {
            $filename = basename($destination);
            echo "{$filename} has already existed, do you want to replace it? [ Y / N (default) ] : ";
            $answer = strtolower(trim(strtoupper(fgets(STDIN))));
            if (!in_array($answer, [ 'y', 'yes' ])) {
                $replace = false;
            }
        }
        if ($replace) {
            FileHelper::copyFile($source, $destination);
        }
    }
}
