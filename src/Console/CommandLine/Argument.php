<?php
declare(strict_types=1);
namespace Karthus\Console\CommandLine;

/**
 * Class Argument
 *
 * @package Karthus\Console\CommandLine
 */
class Argument {
    /**
     * 获取脚本
     * @return string
     */
    public static function script() {
        $argv = $GLOBALS['argv'];
        return $argv[0];
    }
    /**
     * 获取命令
     * @return string
     */
    public static function command() {
        static $command;
        if (!isset($command)) {
            $argv    = $GLOBALS['argv'];
            $command = $argv[1] ?? '';
            $command = preg_match('/^[a-zA-Z0-9_\-:]+$/i', $command) ? $command : '';
            $command = substr($command, 0, 1) == '-' ? '' : $command;
        }
        return trim($command);
    }
}
