<?php
declare(strict_types=1);

namespace Karthus;

use Karthus\Config as GConfig;
use Karthus\Driver\Mysqli\Config as MysqlConfig;
use Karthus\Driver\Pool\Redis\Redis;
use Karthus\Driver\Redis\Config as RedisConfig;
use Karthus\Driver\Pool\Mysql\Mysql;
use Karthus\Http\Request;
use Karthus\Http\Response;
use Karthus\AbstractInterface\Event;
use Karthus\Event\EventRegister;

class KarthusEvent implements Event {

    /**
     * @return mixed|void
     * @throws \ReflectionException
     */
    public static function initialize() {
        // 这里注册 MYSQL 进程池
        $mysqlConfig   = GConfig::getInstance()->getConf("MYSQL");
        ///循环遍历和注册了
        if($mysqlConfig){
            foreach ($mysqlConfig as $key => $item){
                $config     = new MysqlConfig($item);
                Mysql::getInstance()->register($key, $config);
            }
        }

        //这里开始注册 Redis 进程池
        $redisConfig   = GConfig::getInstance()->getConf('REDIS');
        if($redisConfig){
            foreach ($redisConfig as $key => $item){
                $config     = new RedisConfig($item);
                Redis::getInstance()->register($key, $config);
            }
        }
    }

    /**
     * @param EventRegister $register
     * @return mixed|void
     */
    public static function mainServerCreate(EventRegister $register) {}

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
        $path           = strval($request->getUri());
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
