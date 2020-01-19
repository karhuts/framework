<?php
declare(strict_types=1);
namespace Karthus;

use Karthus\Component\Event;
use Karthus\Component\Singleton;
use Karthus\Logger\LoggerInterface;

class Logger implements LoggerInterface {
    use Singleton;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Event
     */
    private $callback;

    /**
     * Logger constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger   = $logger;
        $this->callback = new Event();
    }

    /**
     * @param string|null $msg
     * @param int         $logLevel
     * @param string      $category
     * @return mixed|void
     */
    public function console(?string $msg, int $logLevel = self::LOG_LEVEL_INFO, string $category = 'DEBUG') {
        $this->logger->console($msg, $logLevel, $category);
        $this->logger($msg, $logLevel, $category);
    }


    /**
     * @param string|null $msg
     * @param string      $category
     */
    public function info(?string $msg, string $category = 'DEBUG') {
        $this->console($msg, self::LOG_LEVEL_INFO, $category);
    }

    /**
     * 成功
     *
     * @param string|null $msg
     */
    public function success(?string $msg){
        $this->console($msg, self::LOG_LEVEL_SUCCESS, 'SUCCESS');
    }

    /**
     * @param string|null $msg
     * @param string      $category
     */
    public function notice(?string $msg, string $category = 'DEBUG') {
        $this->console($msg, self::LOG_LEVEL_NOTICE, $category);
    }

    /**
     * @param string|null $msg
     * @param string      $category
     */
    public function waring(?string $msg, string $category = 'DEBUG') {
        $this->console($msg, self::LOG_LEVEL_WARNING, $category);
    }

    /**
     * @param string|null $msg
     * @param string      $category
     */
    public function error(?string $msg, string $category = 'DEBUG') {
        $this->console($msg, self::LOG_LEVEL_ERROR, $category);
    }

    /**
     * @return Event
     */
    public function onLog(): Event {
        return $this->callback;
    }

    /**
     * @param string|null $msg
     * @param int         $logLevel
     * @param string      $category
     * @return string
     */
    public function logger(?string $msg, int $logLevel = self::LOG_LEVEL_INFO, string $category = 'DEBUG'): string {
        $str    = $this->logger->logger($msg, $logLevel, $category);
        $calls  = $this->callback->all();
        foreach ($calls as $call) {
            call_user_func($call, $msg, $logLevel, $category);
        }
        return $str;
    }
}
