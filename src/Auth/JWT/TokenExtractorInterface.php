<?php
declare(strict_types=1);
namespace Karthus\Auth\JWT;

interface TokenExtractorInterface {
    /**
     * 提取token
     * @return string
     */
    public function extractToken();
}
