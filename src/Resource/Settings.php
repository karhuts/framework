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
            'worker_num'    => 8,
            // 是否开启异步
            'reload_async'  => true,
            // 最大等待时间
            'max_wait_time' => 3,
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
        'path'              => KARTHUS_ROOT . '/languages',
    ],
];
