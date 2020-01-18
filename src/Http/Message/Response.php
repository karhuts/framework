<?php
declare(strict_types=1);
namespace Karthus\Http\Message;

use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Status;

class Response extends Message implements ResponseInterface {
    private $statusCode = 200;
    private $reasonPhrase = 'OK';
    private $cookies = [];

    /**
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     * @return $this|ResponseInterface
     */
    public function withStatus($code, $reasonPhrase = '') {
        if($code === $this->statusCode){
            return $this;
        }else{
            $this->statusCode = $code;
            if(empty($reasonPhrase)){
                $this->reasonPhrase = Status::getReasonPhrase($this->statusCode);
            }else{
                $this->reasonPhrase = $reasonPhrase;
            }
            return $this;
        }
    }

    /**
     * @return string
     */
    public function getReasonPhrase() {
        return $this->reasonPhrase;
    }

    /**
     * @param array $cookie
     * @return $this
     */
    public function withAddedCookie(array $cookie){
        $this->cookies[] = $cookie;
        return $this;
    }


    /**
     * @return array
     */
    public function getCookies(){
        return $this->cookies;
    }
}
