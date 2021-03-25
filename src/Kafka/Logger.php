<?php
declare(strict_types=1);
namespace Karthus\Kafka;

use Karthus\Http\Request;
use RuntimeException;

class Logger{
    private $date       = 0;
    private $event_time = 0;
    private $path       = '';
    /**
     * @var Request
     */
    private $request    = null;
    private $data       = [];
    private $code       = 0;
    private $msg        = '';
    private $uid        = 0;
    private $version    = 0;
    private $allowed_mysql = 0;

    public function __construct(Request $request = null) {
        $this->request      = $request;
        $this->date         = date('Ymd');
        $this->event_time   = microtime(true);
    }

    /**
     * 设置存入路径
     *
     * @param string $path
     * @return Logger
     */
    public function setPath(string $path): Logger{
        $this->path = $path;
        return $this;
    }

    /**
     * 设置数值
     *
     * @param array $data
     * @return Logger
     */
    public function setData(array $data): Logger{
        $this->data    = $data;
        return $this;
    }

    /**
     * @param int $code
     * @return Logger
     */
    public function setCode(int $code = 200): Logger{
        $this->code     = $code;
        return $this;
    }

    public function setAllowedMySQL(int $allowed_mysql = 1): Logger{
        $this->allowed_mysql    = $allowed_mysql;
        return $this;
    }

    /**
     * @param string $msg
     * @return Logger
     */
    public function setMsg(string $msg = ''): Logger{
        $this->msg      = $msg;
        return $this;
    }

    /***
     * 设置内容
     *
     * @param string $name
     * @param        $data
     * @return Logger
     */
    public function setBody(string $name, $data) : Logger{
        $this->data[$name]  = $data;

        return $this;
    }

    /**
     * @param int $uid
     * @return Logger
     */
    public function setUID(int $uid = 0): Logger{
        $this->uid      = $uid === 0 ? $this->request->getRemoteUserID() : $uid;
        return $this;
    }

    /***
     * 设置version
     *
     * @param int $version
     * @return Logger
     */
    public function setVersion(int $version = 0): Logger{
        $this->version  = $version;

        return $this;
    }

    /**
     * 执行
     */
    public function execute(): void {
        $_    = array(
            'uid'           => $this->uid,
            'date'          => $this->date,
            'event_time'    => $this->event_time,
            'ip'            => $this->request->getRemoteIP(),
            'request_id'    => $this->request->getRequestID(),
            'code'          => $this->code,
            'message'       => trim($this->msg),
            'ua'            => $this->request->getUserAgent(),
            'lang'          => $this->request->getAcceptLanguage(),
            'data'          => $this->data,
            'path'          => $this->request->getUri()->getUserInfo(),
            'method'        => $this->request->getMethod(),
            'query_string'  => http_build_query($this->request->getQueryParams()),
            'version'       => $this->version,
            'allowed_mysql' => $this->allowed_mysql,
        );


        $path = "/data/logs/{$this->path}/";
        if((is_dir($path) === false) && !mkdir($path) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
        $file = $path . date('Ymd') . '.log';
        @file_put_contents($file, json_encode($_, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    }
}
