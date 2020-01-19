<?php
declare(strict_types=1);

namespace Karthus;

use Karthus\Http\Request;
use Karthus\Http\Response;
use Karthus\AbstractInterface\Event;
use Karthus\Event\EventRegister;

class KarthusEvent implements Event {

    /**
     * @return mixed|void
     */
    public static function initialize() {}

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
