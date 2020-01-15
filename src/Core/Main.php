<?php
declare(strict_types=1);
namespace Karthus\Core;

use Karthus\Console\Application;
use Karthus\Console\Error;
use Karthus\Event\EventDispatcher;
use Karthus\Event\Listeners\CommandListener;
use Karthus\Http\Middleware\GlobalMiddleware;
use Karthus\Http\Server\Server;
use Karthus\Injector\BeanDefinition;
use Karthus\Logger\FileHandler;
use Karthus\Logger\Logger;
use Karthus\Logger\MultiHandler;
use Karthus\Logger\StdoutHandler;
use Karthus\Router\Router;
use Symfony\Component\Dotenv\Dotenv;

class Main {
    private $evn_file = '';
    private $config   = [];
    private $routerPatterns = [
        'uid'   => '\d+',
    ];


    /***
     * Main constructor.
     *
     * @param String $evn_file
     */
    public function __construct(String $evn_file = '') {
        $this->evn_file = $evn_file;
        $this->config   = [
            // 应用名称
            'appName'    => env('APP_NAME', 'Karthus'),
            // 应用版本
            'appVersion' => env('APP_VERSION', '0.0.0'),
            // 应用调试
            'appDebug'   => env('APP_DEBUG', true),
            // 协程配置
            'coroutine'  => [
                true,
                [
                    'max_coroutine' => 300000,
                    'hook_flags'    => SWOOLE_HOOK_ALL,
                ],
            ],
            // 命令
            'commands'   => [
                /** Http */
                'http:start' => [
                    Run::class,
                    'description' => "Start service",
                    'options'     => [
                        [['d', 'daemon'], 'description' => "\tRun in the background"],
                        [['h', 'host'], 'description' => "\tListen to the specified host"],
                        [['p', 'port'], 'description' => "\tListen to the specified port"],
                        [['r', 'reuse-port'], 'description' => "Reuse port in multiple processes"],
                    ],
                ],
            ],
            // 依赖配置
            'beans'      => [
                // 错误
                [
                    // 名称
                    'name'            => 'error',
                    // 作用域
                    'scope'           => BeanDefinition::SINGLETON,
                    // 类路径
                    'class'           => Error::class,
                    // 构造函数注入
                    'constructorArgs' => [
                        // 错误级别
                        E_ALL,
                        // 日志
                        ['ref' => 'log'],
                    ],
                ],
                // 日志
                [
                    // 名称
                    'name'       => 'log',
                    // 作用域
                    'scope'      => BeanDefinition::SINGLETON,
                    // 类路径
                    'class'      => Logger::class,
                    // 属性注入
                    'properties' => [
                        // 日志记录级别
                        'levels'  => ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'],
                        // 处理器
                        'handler' => ['ref' => MultiHandler::class],
                    ],
                ],
                // 日志处理器
                [
                    // 类路径
                    'class'           => MultiHandler::class,
                    // 构造函数注入
                    'constructorArgs' => [
                        // 标准输出处理器
                        ['ref' => StdoutHandler::class],
                        // 文件处理器
                        ['ref' => FileHandler::class],
                    ],
                ],
                // 日志标准输出处理器
                [
                    // 类路径
                    'class' => StdoutHandler::class,
                ],
                // 日志文件处理器
                [
                    // 类路径
                    'class'      => FileHandler::class,
                    // 属性注入
                    'properties' => [
                        // 日志目录
                        'dir'         => __DIR__ . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs',
                        // 日志轮转类型
                        'rotate'      => FileHandler::ROTATE_DAY,
                        // 最大文件尺寸
                        'maxFileSize' => 0,
                    ],
                ],
                // Http服务器
                [
                    // 名称
                    'name'            => 'httpServer',
                    // 类路径
                    'class'           => Server::class,
                    // 构造函数注入
                    'constructorArgs' => [
                        // host
                        '127.0.0.1',
                        // port
                        8000,
                        // ssl
                        false,
                    ],
                ],
                // 事件调度器
                [
                    // 名称
                    'name'            => 'event',
                    // 作用域
                    'scope'           => BeanDefinition::SINGLETON,
                    // 类路径
                    'class'           => EventDispatcher::class,
                    // 构造函数注入
                    'constructorArgs' => [
                        CommandListener::class,
                    ],
                ],
            ],
        ];
    }

    /**
     * 设置APP名称
     *
     * @param String $appName
     * @return Main
     */
    public function setAppName(String $appName = ''): Main {
        $this->config['appName'] = $appName;
        return $this;
    }


    /**
     * 设置协程
     *
     * @param bool  $isOpen
     * @param array $options
     * @return Main
     */
    public function setCoroutine(bool $isOpen = true, array $options = []) : Main{
        $options['max_coroutine']   = $options['max_coroutine'] ?? 300000;
        $options['hook_flags']      = $options['hook_flags'] ?? SWOOLE_HOOK_ALL;
        $this->config['coroutine']  = [!!$isOpen, $options];

        return $this;
    }

    /**
     * 设置路由替换规则
     *
     * @param array $patterns
     * @return Main
     */
    public function setRouterPatterns(array $patterns = []): Main{
        $this->routerPatterns   = empty($patterns) ? $this->routerPatterns : $patterns;
        return $this;
    }

    /***
     * 设置路由
     *
     * @param array $routers
     * @return Main
     */
    public function setRouters(array $routers = []): Main{
        $this->config['beans'][]    = [
            // 名称
            'name'       => 'route',
            // 类路径
            'class'      => Router::class,
            // 初始方法
            'initMethod' => 'parse',
            // 属性注入
            'properties' => [
                // 默认变量规则
                'defaultPattern' => '[\w-]+',
                // 路由变量规则
                'patterns'       => $this->routerPatterns,
                // 全局中间件
                'middleware'     => [GlobalMiddleware::class],
                // 路由规则
                'rules'          => $routers,
            ],
        ];
        return $this;
    }

    /**
     * 设置自动加载
     *
     * @param string $path
     * @return Main
     */
    public function setAutoLoading(string $path = ''): Main{
        //注册一个自动载入
        spl_autoload_register(function($name) use ($path){
            $name   = str_replace("\\", DIRECTORY_SEPARATOR, $name);
            $filename   = "$path/$name.php";

            if(file_exists($filename)){
                include_once($filename);
            }
        });
        return $this;
    }

    /**
     * 运行
     */
    public function run(){
        // 初始化环境变量
        $env    = new Dotenv();
        $env->load($this->evn_file);

        // Run application
        $app = new Application($this->config);
        $app->run();
    }

}
