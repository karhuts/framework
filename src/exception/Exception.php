<?php

namespace karthus\exception;

use Error;
use JetBrains\PhpStorm\NoReturn;
use karthus\support\bootstrap\Mysql;
use karthus\support\Log;
use Throwable;
use function karthus\config;

class Exception extends Error
{

    /**
     * 命令行运行，异常模板
     */
    public static function cliErrorTpl(Error $e): void
    {
        $InitPHP_conf = InitPHP::getConfig();
        $msg = $e->getMessage();
        $mainErrorCode = $e->getCode();
        self::recordError($msg, $e->getFile(), $e->getLine(), trim($mainErrorCode));
        //如果debug关闭，则不显示debug错误信息
        $trace = $e->getTrace();
        $runTrace = $e->getTrace();
        krsort($runTrace);
        $traceMessageHtml = null;
        $k = 1;
        echo "PHP Trace:\r\n";
        foreach ($runTrace as $v) {
            echo "[file]:" . $v['file'] . " \r\n[line]:" . $v['line'] . " \r\n[code]:" . trim(self::getLineCode($v['file'], $v['line'])) . "\r\n\r\n";
            $k++;
        }
        unset($k);
        unset($trace);
        unset($runTrace);
        unset($trace);
        echo "SQL Trace:\r\n";
        if (isset($InitPHP_conf['sqlcontrolarr']) && is_array($InitPHP_conf['sqlcontrolarr'])) {
            foreach ($InitPHP_conf['sqlcontrolarr'] as $k => $v) {
                echo "[Sql]:" . $v['sql'] . " \r\n[queryTime]:" . $v['queryTime'] . " \r\n[affectedRows]:" . $v['affectedRows'] . "\r\n\r\n";
            }
        }
    }

    /***
     * @param Throwable $e
     */
    #[NoReturn] public static function errorTpl(Throwable $e): void
    {
        $host = $_SERVER['HTTP_HOST'];
        $msg = $e->getMessage();
        $is_debug = config('app.is_debug', false);
        $mainErrorCode  = self::getLineCode($e->getFile(), $e->getLine());
        self::recordError($msg, $e->getFile(), $e->getLine(), trim($mainErrorCode));
        if (!$is_debug && $e->getCode() === 10000) {
            $msg = '系统繁忙，请稍后再试';
        }
        if (self::isAjax()) {
            $arr = array('status' => 0, 'message' => $msg, 'data' => array('code' => $e->getCode()));
            echo json_encode($arr);
        } else {
            //网页500
            header('HTTP/1.1 500 Internal Server Error');
            header("status: 500 Internal Server Error");
            $trace = $e->getTrace();
            $runTrace = $e->getTrace();
            krsort($runTrace);
            $traceMessageHtml = "";
            $sqlTraceHtml = '';
            $k = 1;
            foreach ($runTrace as $v) {
                $traceMessageHtml.='<tr class="bg1"><td>'.$k.'</td><td>'.$v['file'].'</td><td>'.$v['line'].'</td><td>'.self::getLineCode($v['file'], $v['line']).'</td></tr>';
                $k++;
            }
            unset($k, $trace, $runTrace, $trace);
            $queries = Mysql::getQueries();
            if ($queries) {
                foreach ($queries as $k => $v) {
                    $bindings = $v['bindings'];
                    $b = "<ul>";
                    foreach ($bindings as $kk => $vv) {
                        $b .= "<li>$vv</li>";
                    }
                    $b .= "</ul>";
                    $sqlTraceHtml.='<tr class="bg1"><td>'.($k+1).'</td><td>'.$v['query'].'</td><td>'.$v['time'].'s</td><td>'.$b.'</td></tr>';
                }
            }
            $error_html_content = <<<EOT
<!doctype html>
<html lang="zh-cn">
  <head>
    <title>$host - PHP Error</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE" />
    <style>
        body {
            background-color: white;
            color: black;
            font: 9pt/11pt verdana, arial, sans-serif;
        }
        
        #container {
            width: 90%;
            margin-left: auto;
            margin-right: auto;
        }
        
        #message {
            width: 90%;
            color: black;
        }
        
        .red {
            color: red;
        }
        
        a:link {
            font: 9pt/11pt verdana, arial, sans-serif;
            color: red;
        }
        
        a:visited {
            font: 9pt/11pt verdana, arial, sans-serif;
            color: #4e4e4e;
        }
        
        h1 {
            color: #FF0000;
            font: 18pt "Verdana";
            margin-bottom: 0.5em;
        }
        
        .bg1 {
            background-color: #FFFFCC;
        }
        
        .bg2 {
            background-color: #EEEEEE;
        }
        
        .table {
            background: #AAAAAA;
            font: 11pt Menlo,Consolas,"Lucida Console"
        }
        
        .info {
            background: none repeat scroll 0 0 #F3F3F3;
            border: 0 solid #aaaaaa;
            border-radius: 10px 10px 10px 10px;
            color: #000000;
            font-size: 11pt;
            line-height: 160%;
            margin-bottom: 1em;
            padding: 1em;
        }
        
        .help {
            background: #F3F3F3;
            border-radius: 10px 10px 10px 10px;
            font: 12px verdana, arial, sans-serif;
            text-align: center;
            line-height: 160%;
            padding: 1em;
        }
        
        .mind {
            background: none repeat scroll 0 0 #FFFFCC;
            border: 1px solid #aaaaaa;
            color: #000000;
            font-size: 9pt;
            line-height: 160%;
            margin-top: 1em;
            padding: 4px;
        }
    </style>
  </head>
  <body>
    <div id="container">
      <h1>Karthus/framework DEBUG</h1>
      <div class="info">$msg</div>
      <div class="info">
        <p><strong>PHP Trace</strong></p>
        <table class="table">
          <tr class="bg2">
            <td style="width: 2%">No.</td>
            <td style="width: 45%">File</td>
            <td style="width: 5%">Line</td>
            <td style="width: 48%">Code</td>
          </tr>
          $traceMessageHtml
        </table>
        <p><strong>SQL Query</strong></p>
        <table cellpadding="5" cellspacing="1" width="100%" class="table">
          <tr class="bg2">
            <td style="width: 2%">No.</td>
            <td style="width: 73%">SQL</td>
            <td style="width: 10%">Cost Time</td>
            <td style="width: 15%">Params</td>
          </tr>
          $sqlTraceHtml
        </table>
      </div>
      <div class="help">
        <a href="http://$host">$host</a>
        已经将此出错信息详细记录, 由此给您带来的访问不便我们深感歉意.
      </div>
    </div>
  </body>
</html>
EOT;
            echo $error_html_content;
        }
        exit();
    }

    /**
     * 判断是否是ajax请求
     * @return bool
     */
    private static function isAjax(): bool
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            $_SERVER['HTTP_X_REQUESTED_WITH'] &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }
        return false;
    }

    /**
     * get error file line code
     * get error file line code
     * @param string $file
     * @param int $line
     * @return string
     */
    private static function getLineCode(string $file, int $line): string
    {
        $fp = fopen($file, 'r');
        $i = 0;
        while(!feof($fp)) {
            $i++;
            $c = fgets($fp);
            if($i==$line) {
                return $c;
            }
        }
        return "";
    }
    /**
     * record error log
     * @param string $msg
     * @param string $file
     * @param int $line
     * @param string $code
     */
    private static function recordError(string $msg, string $file, int $line, string $code): void
    {
        $string ='['.date('Y-m-d h:i:s').']msg:'.$msg.';file:'.$file.';line:'.$line.';code:'.$code.'';
        Log::debug($string);
    }
}
