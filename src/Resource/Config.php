<?php

use Karthus\Server;

return [
    // 服务名称
    'SERVER_NAME'   => "Karthus-Server",
    'MAIN_SERVER'   => [
        // 监听地址，建议使用 0.0.0.0 监听本地多路地址
        'LISTEN_ADDRESS' => '0.0.0.0',
        // 监听端口
        'PORT'      => 8000,
        /**
         * 服务类型
         * 可选为
         * SERVER_TYPE_DEFAULT_WEB  web服务器
         * SERVER_TYPE_DEFAULT  默认tcp服务其
         * SERVER_TYPE_DEFAULT_WEB_SOCKET web-socket服务器
         */
        'SERVER_TYPE'   => Server::SERVER_TYPE_DEFAULT_WEB,
        'SOCK_TYPE'     => SWOOLE_TCP,
        'RUN_MODEL'     => SWOOLE_PROCESS,
        'SETTING'       => [
            'worker_num'    => 8,
            'reload_async'  => true,
            'max_wait_time' =>3
        ],
        'TASK'=>[
            'workerNum'     =>4,
            'maxRunningNum' =>128,
            'timeout'       =>15
        ]
    ],
    'LOG_DIR'               => null
];
