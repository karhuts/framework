<?php
declare(strict_types=1);
namespace Karthus\Auth\JWT;

use Karthus\Exception\ExtractTokenException;
use Psr\Http\Message\MessageInterface;


class BearerTokenExtractor implements TokenExtractorInterface {
    /**
     * @var MessageInterface
     */
    public $request;

    /**
     * BearerTokenExtractor constructor.
     *
     * @param MessageInterface $request
     */
    public function __construct(MessageInterface $request) {
        $this->request = $request;
    }

    /**
     * 提取token
     * @return string
     */
    public function extractToken() {
        $authorization = $this->request->getHeaderLine('authorization');
        if (strpos($authorization, 'Bearer ') !== 0) {
            throw new ExtractTokenException('Failed to extract token.');
        }
        return substr($authorization, 7);
    }
}
