<?php
declare(strict_types=1);

namespace Karthus\Component\Process\Socket;

use Karthus\Component\Process\Config;

class UnixProcessConfig extends Config {
    protected $socketFile;
    protected $asyncCallback = true;

    /**
     * @return mixed
     */
    public function getSocketFile(): string {
        return $this->socketFile;
    }

    /**
     * @param mixed $socketFile
     */
    public function setSocketFile(string $socketFile): void {
        $this->socketFile = $socketFile;
    }

    /**
     * @return bool
     */
    public function isAsyncCallback(): bool {
        return !!$this->asyncCallback;
    }

    /**
     * @param bool $asyncCallback
     */
    public function setAsyncCallback(bool $asyncCallback): void {
        $this->asyncCallback = $asyncCallback;
    }
}
