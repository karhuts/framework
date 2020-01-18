<?php
declare(strict_types=1);
namespace Karthus\AbstractInterface;

use Karthus\Event\EventRegister;
use Karthus\Http\Request;
use Karthus\Http\Response;

interface Event {
    /**
     * @return mixed
     */
    public static function initialize();

    /**
     * @param EventRegister $register
     * @return mixed
     */
    public static function mainServerCreate(EventRegister $register);

    /**
     * @param Request  $request
     * @param Response $response
     * @return bool
     */
    public static function beforeRequest(Request $request,Response $response):bool;

    /**
     * @param Request  $request
     * @param Response $response
     */
    public static function afterRequest(Request $request,Response $response):void;
}
