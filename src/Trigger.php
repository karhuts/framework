<?php
declare(strict_types=1);
namespace Karthus;

use Karthus\Component\Event;
use Karthus\Component\Singleton;
use Karthus\Trigger\Location;
use Karthus\Trigger\TriggerInterface;

class Trigger implements TriggerInterface {
    use Singleton;

    /**
     * @var TriggerInterface
     */
    private $trigger;

    private $onError;
    private $onException;

    /**
     * Trigger constructor.
     *
     * @param TriggerInterface $trigger
     */
    public function __construct(TriggerInterface $trigger) {
        $this->trigger     = $trigger;
        $this->onError     = new Event();
        $this->onException = new Event();
    }

    /**
     * @param               $msg
     * @param int           $errorCode
     * @param Location|null $location
     * @return mixed|void
     */
    public function error($msg,int $errorCode = E_USER_ERROR,
                          Location $location = null) {
        // TODO: Implement error() method.
        if($location == null){
            $location = $this->getLocation();
        }
        $this->trigger->error($msg,$errorCode, $location);
        $all = $this->onError->all();
        foreach ($all as $call){
            call_user_func($call,$msg,$errorCode,$location);
        }
    }

    /**
     * @param \Throwable $throwable
     * @return mixed|void
     */
    public function throwable(\Throwable $throwable) {
        $this->trigger->throwable($throwable);
        $all = $this->onException->all();
        foreach ($all as $call){
            call_user_func($call,$throwable);
        }
    }

    /**
     * @return Event
     */
    public function onError():Event {
        return $this->onError;
    }

    /**
     * @return Event
     */
    public function onException():Event {
        return $this->onException;
    }

    /**
     * @return Location
     */
    private function getLocation():Location {
        $location = new Location();
        $debugTrace = debug_backtrace();
        array_shift($debugTrace);
        $caller = array_shift($debugTrace);
        $location->setLine($caller['line']);
        $location->setFile($caller['file']);
        return $location;
    }
}
