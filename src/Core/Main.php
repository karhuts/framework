<?php
declare(strict_types=1);
namespace Karthus\Core;

use Karthus\Console\Application;
use Karthus\Console\Error;
use Karthus\Database\Connection;
use Karthus\Database\Pool\ConnectionPool;
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

    /**
     * APP名称
     *
     * @var string
     */
    private $app_name       = '';
    /**
     * APP版本号
     *
     * @var string
     */
    private $app_version    = '0.0.0';
    /**
     * 是否开启debug调试模式
     *
     * @var bool
     */
    private $app_debug      = false;
    private $evn_file = '';
    private $config   = [];
    private $routerPatterns = [
        'uid'   => '\d+',
    ];


    /**
     * Main constructor.
     *
     * @param string $appName
     * @param string $version
     * @param bool   $debug
     */
    public function __construct(string $appName = '', string $version = '0.0.0', bool $debug = false) {
        $this->app_version  = $version;
        $this->app_name     = $appName;
        $this->app_debug    = !!$debug;
        $this->config   = [
            // 应用名称
            'appName'    => $this->app_name,
            // 应用版本
            'appVersion' => $this->app_version,
            // 应用调试
            'appDebug'   => $this->app_debug,
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
        $this->app_name          = strval($appName);
        $this->config['appName'] = $this->app_name;
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


    /**
     * 设置日志文件处理器配置
     *
     * @param string $logger_dir
     * @param int    $rotate
     * @param int    $maxFileSize
     * @return Main
     */
    public function setLoggerConfig(string $logger_dir = '',
                                    int $rotate = FileHandler::ROTATE_HOUR,
                                    int $maxFileSize = 0): Main{
        $this->config['beans'][]  = [
            // 类路径
            'class'             => FileHandler::class,
            // 属性注入
            'properties'        => [
                // 日志目录
                'dir'           => $logger_dir,
                // 日志轮转类型
                'rotate'        => $rotate,
                // 最大文件尺寸
                'maxFileSize'   => $maxFileSize,
            ],
        ];
        return $this;
    }

    /***
     * 设置连接池
     *
     * @param string $pool_name
     * @param array  $config
     * @return Main
     */
    public function setDataBasePool(string $pool_name = 'dbPool',
                                    array $config = []): Main{

        $maxIdle        = $config['maxIdle'] ?? 5;
        $maxActive      = $config['maxActive'] ?? 50;
        $host           = $config['host'] ?? '127.0.0.1';
        $port           = $config['port'] ?? 3306;
        $password       = $config['password'] ?? '';
        $username       = $config['username'] ?? '';
        $database       = $config['database'] ?? 'blued';

        $this->config['beans'][] = [
            // 名称
            'name'       => 'dbPool',
            // 作用域
            'scope'      => BeanDefinition::SINGLETON,
            // 类路径
            'class'      => ConnectionPool::class,
            // 属性注入
            'properties' => [
                // 最多可空闲连接数
                'maxIdle'         => $maxIdle,
                // 最大连接数
                'maxActive'       => $maxActive,
                // 拨号器
                'dialer'          => ['ref' => DatabaseDialer::class],
                // 事件调度器
                'eventDispatcher' => ['ref' => 'event'],
            ],
        ];
        // Database连接池拨号
        $this->config['beans'][] = [
                // 类路径
                'class' => DatabaseDialer::class,
        ];
        // Database连接
        $this->config['beans'][]       = [
            // 类路径
            'class'      => Connection::class,
            // 初始方法
            'initMethod' => 'connect',
            // 属性注入
            'properties' => [
                // 数据库IP
                'host'            => $host,
                // 数据库端口
                'port'            => $port,
                // 数据库用户名
                'username'        => $username,
                // 数据库密码
                'password'        => $password,
                // 数据库
                'database'        => $database,
                // 事件调度器
                'eventDispatcher' => ['ref' => 'event'],
            ],
        ];

        return $this;
    }

    /**
     * 是否开启DEBUG模式
     *
     * @param bool $debug
     * @return Main
     */
    public function setDebug(bool $debug = false): Main{
        $this->app_debug            = !!$debug;
        $this->config['appDebug']   = $this->app_debug;
        return $this;
    }


    /**
     * 设置APP版本号
     *
     * @param string $version
     * @return Main
     */
    public function setVersion(string $version = '0.0.0'): Main{
        $this->app_version          = $version;
        $this->config['appVersion'] = $this->app_version;
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
     * 运行
     */
    public function run(){
        // Run application
        $app = new Application($this->config);
        $app->run();
    }

}
