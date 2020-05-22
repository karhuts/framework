<?php
declare(strict_types=1);
namespace Karthus\Task;

/**
 * Class Protocol
 *
 * @package Karthus\Task
 */
class Protocol {
    /**
     * @param string $data
     * @return string
     */
    public static function pack(string $data): string {
        return pack('N', strlen($data)) . $data;
    }

    /**
     * @param string $head
     * @return int
     */
    public static function packDataLength(string $head): int {
        return unpack('N', $head)[1];
    }

    /**
     * @param string $data
     * @return string
     */
    public static function unpack(string $data): string {
        return substr($data, 4);
    }
}
