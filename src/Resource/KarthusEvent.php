<?php
declare(strict_types=1);

namespace Karthus;

use Karthus\Config as GConfig;
use Karthus\Driver\Pool\Mysql\Connection;
use Karthus\Driver\Pool\Mysql\Manager;
use Karthus\Driver\Pool\Redis\Redis;
use Karthus\Driver\Redis\ClusterConfig as RedisClusterConfig;
use Karthus\Http\Request;
use Karthus\Http\Response;
use Karthus\AbstractInterface\Event;
use Karthus\Event\EventRegister;
use Karthus\Driver\Pool\Mysql\Config as MysqlConfig;

class KarthusEvent implements Event {

    /**
     * @return mixed|void
     * @throws \ReflectionException
     */
    public static function initialize() {
        date_default_timezone_set('Asia/Shanghai');
        // 这里注册 MYSQL 进程池
        $mysqlConfig   = GConfig::getInstance()->getConf("MYSQL");
        ///循环遍历和注册了
        if($mysqlConfig){
            $account    = $mysqlConfig['account'] ?? [];
            $serversInfo= $mysqlConfig['serverInfo'] ?? [];

            if($account && $serversInfo){
                foreach ($serversInfo as $serverName => $servers){
                    if(empty($servers)){
                        continue;
                    }

                    foreach ($servers as $model => $server){
                        //获取帐号
                        $accountInfo    = $account[$server['account']];
                        $server         = array_merge($server, $accountInfo);
                        unset($server['account']);

                        // 进行配置注入
                        $config         = new MysqlConfig($server);
                        $model          = strtoupper($model);
                        Manager::getInstance()->register(new Connection($config),
                            "{$serverName}_{$model}");
                    }
                }
            }
        }

        //这里开始注册 Redis 进程池
        $redisConfig   = GConfig::getInstance()->getConf('REDIS');
        if($redisConfig){
            foreach ($redisConfig as $key => $item){
                if(is_string($item)){
                    //强制变成集群
                    $_config[] = $item;
                    unset($item);
                    $item     = $_config;
                }

                $clusterConfig  = new RedisClusterConfig($item);
                Redis::getInstance()->register($key, $clusterConfig);
            }
        }
    }

    /**
     * @param EventRegister $register
     * @return mixed|void
     */
    public static function mainServerCreate(EventRegister $register) {
        // TODO 加入定时器
        // 这个定时器为了定时去检查 Qconf中的数据是否还存在
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @return bool
     */
    public static function beforeRequest(Request $request, Response $response): bool {
        return true;
    }

    /**
     * @param Request  $request
     * @param Response $response
     */
    public static function afterRequest(Request $request, Response $response): void {
        $request_id     = $request->getRequestID();
        $request_time   = $request->getRequestTime();
        $start_time     = $request->getRequestTimeFloat();

        $time           = strftime('[%d/%h/%Y:%H:%M:%S %z]', $request_time);
        $id             = strval($request_id);
        $ip             = strval($request->getRemoteIP());
        $uid            = intval($request->getRemoteUserID());
        $method         = strval($request->getMethod());
        $path           = strval($request->getUri()->getPath());
        $ua             = strval($request->getUserAgent());
        $lang           = strval($request->getAcceptLanguage());
        $remoteAddr     = strval($request->getRemoteAddr());

        $endTime        = microtime(true);
        $spend          = $endTime - $start_time;
        $spend          = round($spend, 6) * 1000;
        $msg            = "{$ip} {$uid} {$id} {$time} \"{$method} {$path}\" \"{$ua}\" \"{$lang}\" \"$remoteAddr\" Run Ok[{$spend}ms]!!!";
        Logger::getInstance()->success($msg);
    }
}
