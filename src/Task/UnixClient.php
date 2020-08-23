<?php
declare(strict_types=1);
namespace Karthus\Task;

use Swoole\Coroutine\Client;

class UnixClient {
    private $client = null;

    /**
     * UnixClient constructor.
     *
     * @param string $unixSock
     */
    public function __construct(string $unixSock) {
        $this->client = new Client(SWOOLE_UNIX_STREAM);
        $this->client->set([
            'open_length_check'     => true,
            'package_length_type'   => 'N',
            'package_length_offset' => 0,
            'package_body_offset'   => 4,
            'package_max_length'    => 1024 * 1024
        ]);
        $this->client->connect($unixSock, 0, 3);
    }

    public function __destruct() {
        // TODO: Implement __destruct() method.
        if ($this->client->isConnected()) {
            $this->client->close();
        }
    }


    public function close() {
        if ($this->client->isConnected()) {
            $this->client->close();
        }
    }

    /**
     * @param string $rawData
     * @return bool|mixed
     */
    public function send(string $rawData) {
        if ($this->client->isConnected()) {
            return $this->client->send($rawData);
        } else {
            return false;
        }
    }

    /**
     * @param float $timeout
     * @return array|mixed|null
     */
    public function recv(float $timeout = 0.1) {
        if ($this->client->isConnected()) {
            $ret = $this->client->recv($timeout);
            if (!empty($ret)) {
                return $ret;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}
