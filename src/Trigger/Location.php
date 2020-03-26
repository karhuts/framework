<?php
declare(strict_types=1);
namespace Karthus\Trigger;

class Location {
    private $file;
    private $line;

    /**
     * @return mixed
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file): void {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getLine() {
        return $this->line;
    }

    /**
     * @param mixed $line
     */
    public function setLine($line): void {
        $this->line = $line;
    }
}
