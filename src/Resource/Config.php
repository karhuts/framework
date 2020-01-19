<?php
declare(strict_types=1);
use Karthus\Server;

return [
    // 服务名称
    'SERVER_NAME'   => "Karthus-Server",
    // 主服务监听信息
    'MAIN_SERVER'   => [
        // 监听地址，建议使用 0.0.0.0 监听本地多路地址
        'LISTEN_ADDRESS' => '0.0.0.0',
        // 监听端口
        'PORT'      => 8000,
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
        'TASK'          =>[
            // 异步任务数量
            'workerNum'     => 4,
            // 最大同时运行数量
            'maxRunningNum' => 128,
            // 超时时间
            'timeout'       => 15,
        ]
    ],

    'LOG_DIR'               => null,
    'MYSQL_BLUEDMIS'        => [

    ],
];
