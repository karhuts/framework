<?php
declare(strict_types=1);
namespace Karthus\Logger;

use Karthus\Http\Request;

class Logger implements LoggerInterface {
    private $logDir;

    /**
     * Logger constructor.
     *
     * @param string|null $logDir
     */
    public function __construct(string $logDir = null) {
        if(empty($logDir)){
            $logDir     = getcwd();
        }
        $this->logDir   = $logDir;
    }

    /**
     * 写入日志文件
     *
     * @param string|null $msg
     * @param int         $logLevel
     * @param string      $category
     * @return string
     */
    public function logger(?string $msg,int $logLevel = self::LOG_LEVEL_INFO,
                           string $category = 'DEBUG'):string {
        $datetime   = date("Ymd");
        $levelStr   = $this->levelMap($logLevel);
        $filename   = strtolower($levelStr) . ".log.$datetime";
        $filePath   = $this->logDir."/$filename";
        $str        = "$msg\n";
        @file_put_contents($filePath, $str, FILE_APPEND | LOCK_EX);
        return $str;
    }

    /**
     * @param string|null $msg
     * @param int         $logLevel
     * @param string      $category
     * @return mixed|void
     */
    public function console(?string $msg,int $logLevel = self::LOG_LEVEL_INFO,string $category = 'DEBUG') {
        $date       = date('Y-m-d H:i:s');
        $levelStr   = $this->levelMap($logLevel);
        $temp       =  $this->colorString("[{$date}][{$category}][{$levelStr}] : [{$msg}]",$logLevel)."\n";
        fwrite(STDOUT, $temp);
    }

    /**
     * @param string $str
     * @param int    $logLevel
     * @return string
     */
    private function colorString(string $str,int $logLevel) {
        switch($logLevel) {
            case self::LOG_LEVEL_NOTICE:
                $out = "[43m";
                break;
            case self::LOG_LEVEL_WARNING:
                $out = "[45m";
                break;
            case self::LOG_LEVEL_ERROR:
                $out = "[41m";
                break;
            default:
                $out = "[42m";
                break;
        }
        return chr(27) . "$out" . "{$str}" . chr(27) . "[0m";
    }

    /**
     * @param int $level
     * @return string
     */
    private function levelMap(int $level) {
        switch ($level) {
            case self::LOG_LEVEL_INFO:
                return 'INFO';
            case self::LOG_LEVEL_NOTICE:
                return 'NOTICE';
            case self::LOG_LEVEL_WARNING:
                return 'WARNING';
            case self::LOG_LEVEL_SUCCESS:
                return "SUCCESS";
            case self::LOG_LEVEL_ERROR:
                return 'ERROR';
            default:
                return 'UNKNOWN';
        }
    }
}
