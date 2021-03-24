<?php
declare(strict_types=1);
namespace Karthus\Event;

use Swoole\Server;

class EventHelper {
    /**
     * @param EventRegister $register
     * @param string        $event
     * @param callable      $callback
     */
    public static function register(EventRegister $register,string $event,callable $callback):void {
        $register->set($event,$callback);
    }

    /**
     * @param EventRegister $register
     * @param string        $event
     * @param callable      $callback
     */
    public static function registerWithAdd(EventRegister $register,string $event,callable $callback):void {
        $register->add($event,$callback);
    }

    /**
     * @param Server   $server
     * @param string   $event
     * @param callable $callback
     */
    public static function on(Server $server, string $event, callable $callback): void{
        $server->on($event,$callback);
    }
}
