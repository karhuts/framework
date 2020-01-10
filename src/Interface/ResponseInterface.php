<?php
declare(strict_types=1);

namespace Karthus\Contract;

use Karthus\Contract\Able\Arrayable;
use Karthus\Contract\Able\Jsonable;
use Karthus\Contract\Able\Xmlable;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;


interface ResponseInterface {
    /**
     * Format data to JSON and return data with Content-Type:application/json header.
     *
     * @param array|Arrayable|Jsonable $data
     * @return PsrResponseInterface
     */
    public function json($data): PsrResponseInterface;

    /**
     * Format data to XML and return data with Content-Type:application/xml header.
     *
     * @param array|Arrayable|Xmlable $data
     * @param string                  $root the name of the root node
     * @return PsrResponseInterface
     */
    public function xml($data, string $root = 'root'): PsrResponseInterface;

    /**
     * Format data to a string and return data with Content-Type:text/plain header.
     *
     * @param mixed $data
     * @return PsrResponseInterface
     */
    public function raw($data): PsrResponseInterface;

    /**
     * Redirect to a URL.
     */
    public function redirect(string $toUrl, int $status = 302, string $schema = 'http'): PsrResponseInterface;

    /**
     * Create a file download response.
     *
     * @param string $file the file path which want to send to client
     * @param string $name the alias name of the file that client receive
     * @return PsrResponseInterface
     */
    public function download(string $file, string $name = ''): PsrResponseInterface;
}
