<?php

declare(strict_types=1);
namespace Karthus\Service;

class Autoload{
    private static $loadedFiles = [];

    /**
     * 初始化自动载入
     *
     * @param string $dir
     */
    public static function init(string $dir = __DIR__){
        //注册一个自动载入
        spl_autoload_register(function($name) use ($dir){
            $name   = str_replace("\\", DIRECTORY_SEPARATOR, $name);
            $filename   = "$dir/$name.php";
            $md5        = md5($filename);
            if(file_exists($filename) && !isset(self::$loadedFiles[$md5])){
                include_once($filename);
                self::$loadedFiles[$md5] = $filename;
            }
        });
    }
}
