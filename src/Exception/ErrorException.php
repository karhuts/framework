<?php
declare(strict_types=1);
namespace Karthus\Exception;

/**
 * Class ErrorException
 *
 * @package Karthus\Console\Exception
 */
class ErrorException extends \RuntimeException {

    /**
     * ErrorException constructor.
     *
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     */
    public function __construct($type, $message, $file, $line) {
        $this->code = $type;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        // 父类构造
        parent::__construct();
    }
}
