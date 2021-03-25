<?php
declare(strict_types=1);
use Karthus\Server;

$qconf  = new \Qconf();
$users  = $qconf->getAllHost('/blued/backend/umem/users', '', 1);

return [
    // 服务名称
    'SERVER_NAME'   => "Karthus-Server",
    // 主服务监听信息
    'MAIN_SERVER'   => [
        // 监听地址，建议使用 0.0.0.0 监听本地多路地址
        'LISTEN_ADDRESS' => '0.0.0.0',
        // 监听端口
        'PORT'           => 8000,
        /**
         * 服务类型
         * 可选为
         * Server::SERVER_TYPE_DEFAULT_WEB  web服务器
         * Server::SERVER_TYPE_DEFAULT  默认tcp服务其
         * Server::SERVER_TYPE_DEFAULT_WEB_SOCKET web-socket服务器
         */
        'SERVER_TYPE'   => Server::SERVER_TYPE_DEFAULT_WEB,
        'SOCK_TYPE'     => SWOOLE_TCP,
        'RUN_MODEL'     => SWOOLE_PROCESS,
        'SETTING'       => [
            // worker数量
            'worker_num'        => 8,
            // 设置异步重启开关
            // 设置异步重启开关。设置为 true 时，将启用异步安全重启特性，Worker 进程会等待异步事件完成后再退出。
            // 详细信息请参见 如何正确的重启服务
            'reload_async'      => true,
            // 最大等待时间
            'max_wait_time'     => 3,
            // 开启CPU 亲和
            'open_cpu_affinity' => true,
            // 设置 worker 进程的最大任务数。【默认值：0 即不会退出进程】
            // 一个 worker 进程在处理完超过此数值的任务后将自动退出，进程退出后会释放所有内存和资源
            'max_request'       => 100000,
            // 数据包分发策略。【默认值：2】
            // 1	轮循模式	收到会轮循分配给每一个 Worker 进程
            // 2	固定模式	根据连接的文件描述符分配 Worker。这样可以保证同一个连接发来的数据只会被同一个 Worker 处理
            // 3	抢占模式	主进程会根据 Worker 的忙闲状态选择投递，只会投递给处于闲置状态的 Worker
            // 4	IP 分配	根据客户端 IP 进行取模 hash，分配给一个固定的 Worker 进程。
            // 可以保证同一个来源 IP 的连接数据总会被分配到同一个 Worker 进程。算法为 ip2long(ClientIP) % worker_num
            // 5	UID 分配	需要用户代码中调用 Server->bind() 将一个连接绑定 1 个 uid。然后底层根据 UID 的值分配到不同的 Worker 进程。
            // 算法为 UID % worker_num，如果需要使用字符串作为 UID，可以使用 crc32(UID_STRING)
            // 7	stream 模式	空闲的 Worker 会 accept 连接，并接受 Reactor 的新请求
            'dispatch_mode'     => 3,
            // 开启后 TCP 连接发送数据时会关闭 Nagle 合并算法，立即发往对端 TCP 连接。在某些场景下，如命令行终端，敲一个命令就需要立马发到服务器，可以提升响应速度，请自行 Google Nagle 算法。
            // 启用 open_tcp_nodelay【默认值：false】
            'open_tcp_nodelay'  => true,
        ],
        'TASK'          => [
            // 异步任务数量
            'workerNum'     => 4,
            // 最大同时运行数量
            'maxRunningNum' => 128,
            // 超时时间
            'timeout'       => 15,
        ]
    ],
    //日志存储目录
    'LOG_DIR'               => null,
    'MYSQL'                 => [
        'account'       => [
            'bluedmis'  => [
                'user'          => 'bluedmis',
                'password'      => 'PQeWUC3MdC3wDjcs',
                'database'      => 'bluedmis',
                'charset'       => 'utf8mb4',
                'strict_type'   => true, //开启严格模式，query方法返回的数据也将转为强类型
                'fetch_mode'    => true, //开启fetch模式, 可与pdo一样使用fetch/fetchAll逐行或获取全部结果集(4.0版本以上)
                'timeout'       => 30,
            ]
        ],
        'serverInfo'    => [
            'BLUEDMIS'          => [
                'read'  => [
                    'host'      => [
                        '10.9.196.184'
                    ],
                    'port'      => 3306,
                    'account'   => 'bluedmis',
                ],
                'write' => [
                    'host'      => [
                        '10.9.196.184'
                    ],
                    'port'      => 3306,
                    'account'   => 'bluedmis',
                ],
            ],
        ],
    ],
    'REDIS'                 => [
        // 集群方式
        'USERS'             => $users,
        // 非集群方式
        'TICKTOCKS'         => '10.10.159.251:6379',
    ],
    // 国际化语言包
    'I18N'                  => [
        // 默认语言
        'locale'            => 'zh_CN',
        // 回退语言，当默认语言的语言文本没有提供时，就会使用回退语言的对应语言文本
        'fallback_locale'   => 'en',
        // 语言文件存放的文件夹
        // Karthus 会自动扫描 path 下所有语言包信息
        'path'              => KARTHUS_ROOT . '/Languages',
    ],
    'METRICS'               => [
        // 是否使用 独立监控进程。推荐开启。关闭后将在 Worker 进程 中处理指标收集与上报。
        'use_standalone_process'    => true,
        // 是否统计默认指标。默认指标包括内存占用、系统 CPU 负载以及 Swoole Server 指标和 Swoole Coroutine 指标。
        'enable_default_metric'     => true,
        //  默认指标推送周期，单位为秒，下同。
        'default_metric_interval'   => 5,
    ],
    // 模板视图配置
    'VIEW'  => [
        // 模板目录
        'template_path'         => 'View',
        // 编译目录
        'template_c_path'       => KARTHUS_ROOT. '/Data/Tpl',
        // 模板文件类型
        'template_type'         => 'html',
        // 模板编译文件类型
        'template_c_type'       => 'tpl.php',
        // 左标签
        'template_tag_left'     => '<!--{',
        // 右标签
        'template_tag_right'    => '}-->',
        // 是否需要每次编译
        'is_compile'            => true,
        'is_view_filter'        => true,
    ],
];
