<?php
declare(strict_types=1);
namespace Karthus\Auth\JWT;

class Authorization {
    /**
     * jwt
     * @var JWT
     */
    public $jwt;

    /**
     * Authorization constructor.
     * @param JWT $jwt
     */
    public function __construct(JWT $jwt) {
        $this->jwt = $jwt;
    }

    /**
     * 获取Payload
     * @param TokenExtractorInterface $tokenExtractor
     * @return array
     */
    public function getPayload(TokenExtractorInterface $tokenExtractor) {
        $token = $tokenExtractor->extractToken();
        return $this->jwt->parse($token);
    }

    /**
     * 创建token
     * @param array $payload
     * @return string
     */
    public function createToken(array $payload) {
        return $this->jwt->create($payload);
    }
}
