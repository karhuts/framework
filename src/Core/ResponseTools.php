<?php
declare(strict_types=1);
namespace Karthus\Core;

use Karthus\Core\Help\Json;
use Karthus\Core\Help\Xml;
use Karthus\Http\Message\Factory\StreamFactory;
use Karthus\Http\Message\Response;

class ResponseTools {

    /**
     * html
     * @param Response $response
     * @param string $content
     * @return Response
     */
    public static function html(Response $response, string $content) {
        $body = (new StreamFactory())->createStream($content);
        return $response
            ->withContentType('text/html', 'utf-8')
            ->withBody($body);
    }

    /**
     * json
     * @param Response $response
     * @param array $content
     * @return Response
     */
    public static function json(Response $response, array $content) {
        $status = $content['code'] ?? 200;
        $status = intval($status);
        $body   = (new StreamFactory())->createStream(Json::encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response
            ->withStatus($status)
            ->withContentType('application/json', 'utf-8')
            ->withBody($body);
    }
    /**
     * xml
     * @param Response $response
     * @param array $content
     * @return Response
     */
    public static function xml(Response $response, array $content) {
        $body = (new StreamFactory())->createStream(Xml::encode($content));
        return $response
            ->withContentType('application/xml', 'utf-8')
            ->withBody($body);
    }
}
