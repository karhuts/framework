<?php
declare(strict_types=1);
namespace Karthus\Http;

use Swoole\Http\Status;
use Swoole\Http\Response as SWResponse;
use Karthus\Http\Message\Response as MessageResponse;

class Response extends MessageResponse {
    /**
     * @var SWResponse|null
     */
    private $response;
    public const STATUS_NOT_END         = 0;
    public const STATUS_LOGICAL_END     = 1;
    public const STATUS_REAL_END        = 2;
    public const STATUS_RESPONSE_DETACH = 3;

    private $sendFile;
    private $isEndResponse  = self::STATUS_NOT_END; //1 逻辑end  2真实end 3分离响应
    private $isChunk        = false;

    /**
     * Response constructor.
     *
     * @param SWResponse|null $response
     */
    final public function __construct(SWResponse $response = null) {
        $this->response = $response;
        parent::__construct();
        $this->withAddedHeader('Server','Karthus-Server');
    }

    /**
     * 结束
     */
    public function end(): void {
        $this->isEndResponse = self::STATUS_LOGICAL_END;
    }

    /**
     * @return bool
     */
    public function response():bool {
        if($this->isEndResponse <= self::STATUS_REAL_END){
            $this->isEndResponse = self::STATUS_REAL_END;
            //结束处理
            $status     = $this->getStatusCode();
            $this->response->status($status);
            $headers    = $this->getHeaders();
            foreach ($headers as $header => $val){
                foreach ($val as $sub){
                    $this->response->header($header,$sub);
                }
            }
            $cookies = $this->getCookies();
            foreach ($cookies as $cookie){
                $this->response->cookie(...$cookie);
            }
            $write = $this->getBody()->__toString();
            if($write !== '' && $this->isChunk){
                $this->response->write($write);
                $write = null;
            }

            if($this->sendFile !== null){
                $this->response->sendfile($this->sendFile);
            }else{
                $this->response->end($write);
            }
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function isEndResponse(): int{
        return $this->isEndResponse;
    }

    /**
     * @param string $str
     * @return bool
     */
    public function write(string $str): bool {
        if(!$this->isEndResponse()){
            $this->getBody()->write($str);
            return true;
        }

        return false;
    }

    /**
     * @param $url
     * @param $status
     * @return bool
     */
    public function redirect($url, $status = Status::MOVED_PERMANENTLY): bool {
        if(!$this->isEndResponse()){
            //仅支持header重定向  不做meta定向
            $this->withStatus($status);
            $this->withHeader('Location', $url);
            return true;
        }

        return false;
    }

    /**
     * @param        $name
     * @param null   $value
     * @param null   $expire
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httpOnly
     * @return bool
     */
    public function setCookie($name,
                              $value = null,
                              $expire = null,
                              $path = '/',
                              $domain = '',
                              $secure = false,
                              $httpOnly = false): bool {
        if(!$this->isEndResponse()){
            $this->withAddedCookie([
                $name, $value, $expire, $path, $domain, $secure, $httpOnly
            ]);
            return true;
        }

        return false;

    }

    /**
     * @return SWResponse|null
     */
    public function getSwooleResponse(): ?SWResponse {
        return $this->response;
    }


    /**
     * @param string $sendFilePath
     */
    public function sendFile(string $sendFilePath): void {
        $this->sendFile = $sendFilePath;
    }

    /**
     * @return int|null
     */
    public function detach():?int {
        $fd = $this->response->fd;
        $this->isEndResponse = self::STATUS_RESPONSE_DETACH;
        $this->response->detach();
        return $fd;
    }

    /**
     * @param bool $isChunk
     */
    public function setIsChunk(bool $isChunk): void {
        $this->isChunk = $isChunk;
    }

    /**
     * @param int $fd
     * @return Response
     */
    static function createFromFd(int $fd): Response {
        $resp = SWResponse::create($fd);
        return new Response($resp);
    }

    /**
     * @return string
     */
    final public function __toString():string {
        return "";
    }

    public function __destruct() {
        $this->getBody()->close();
    }
}
