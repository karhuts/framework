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
    }
}
