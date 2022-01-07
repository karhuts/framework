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
 _              _   _               
| | ____ _ _ __| |_| |__  _   _ ___ 
| |/ / _` | '__| __| '_ \| | | / __|
|   < (_| | |  | |_| | | | |_| \__ \
|_|\_\__,_|_|   \__|_| |_|\__,_|___/
                                     Version: {$appVersion}, Swoole: {$swooleVersion}
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
        $argv       = $_SERVER['argv'] ?? [];
        $count      = count($argv);
        $funcName   = $argv[$count - 1];
        [$schema, $option] = explode(':', $funcName);
        $className  = Http::class;
        if($schema === "http") {}

        switch ($option) {
            case 'start':
                new $className();
                break;
            case 'reload':
                new $className('reload');
                break;
            case 'stop':
                new $className('stop');
                break;
            default:
                self::echoError("use $argv[0] [http:start]");
        }
    }
}