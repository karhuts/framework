<?php
declare(strict_types=1);

namespace Karthus\Spl;

/**
 * Class SplFileStream
 *
 * @package Karthus\Spl
 */
class SplFileStream extends SplStream {
    /**
     * SplFileStream constructor.
     *
     * @param        $file
     * @param string $mode
     */
    public function __construct($file,$mode = 'c+') {
        $fp = fopen($file,$mode);
        parent::__construct($fp);
    }

    /**
     * @param int $mode
     * @return bool
     */
    public function lock($mode = LOCK_EX){
        return flock($this->getStreamResource(),$mode);
    }

    /**
     * @param int $mode
     * @return bool
     */
    public function unlock($mode = LOCK_UN){
        return flock($this->getStreamResource(),$mode);
    }
}
