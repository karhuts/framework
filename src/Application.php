<?php
declare(strict_types=1);

namespace Karthus;

use Karthus\Server\Http;

class Application {
    /**
     * @var string
     */
    protected static $version = '3.0.1';

    /**
     * @return void
     */
    public static function welcome(): void {
        $appVersion = self::$version;
        $swooleVersion = SWOOLE_VERSION;
        echo <<<EOL
  ____    _                           
 / ___|  (_)  _ __ ___    _ __    ___ 
 \\___ \\  | | | '_ ` _ \\  | '_ \\  / __|
  ___) | | | | | | | | | | |_) | \\__ \\
 |____/  |_| |_| |_| |_| | .__/  |___/
                         |_|           Version: {$appVersion}, Swoole: {$swooleVersion}
EOL;
    }

    /**
     * @param string $strings
     * @return void
     */
    public static function println(string $strings): void {
        echo $strings . PHP_EOL;
    }

    /**
     * @param string $msg
     * @return void
     */
    public static function echoSuccess(string $msg) : void  {
        self::println('[' . date('Y-m-d H:i:s') . '] [INFO] ' . "\033[32m{$msg}\033[0m");
    }

    public static function echoError(string $msg): void {
        self::println('[' . date('Y-m-d H:i:s') . '] [ERROR] ' . "\033[31m{$msg}\033[0m");
    }

    /**
     * @return void
     */
    public static function run(): void {
        self::welcome();
        global $argv;
        $count = count($argv);
        $funcName = $argv[$count - 1];
        [$schema, $option] = explode(':', $funcName);;
        switch ($schema) {
            case 'http':
                $className = Http::class;
                break;
            default:
                // 用户自定义server
                $configs = config('servers', []);
                if (isset($configs[$schema]['class_name'])) {
                    $className = $configs[$schema]['class_name'];
                } else {
                    self::echoError("command $schema is not exist, you can use $argv[0] [http:start]");
                    exit();
                }
        }
        switch ($option) {
            case 'start':
                new $className();
                break;
            default:
                self::echoError("use $argv[0] [http:start]");
        }
    }
}